<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExceptionService
{
    public function __construct(
        protected EtaService $etaService
    ) {}

    /**
     * Canonical supported exception types.
     */
    public const SUPPORTED_TYPES = [
        'CUSTOMS_HOLD',
        'WEATHER_DELAY',
        'PORT_CONGESTION',
        'DAMAGED',
        'MISROUTED',
        'DOCUMENTATION_ISSUE',
    ];

    /**
     * Log a new exception for a shipment and escalate shipment lifecycle & timeline.
     *
     * @param Shipment $shipment
     * @param array $data
     * @param User|null $actor
     * @return ShipmentException
     * @throws ValidationException
     */
    public function logException(Shipment $shipment, array $data, ?User $actor = null): ShipmentException
    {
        $rawType = strtoupper(trim($data['type'] ?? ''));
        if (!in_array($rawType, self::SUPPORTED_TYPES, true)) {
            // Also check friendly match if snake_case passed e.g. customs_hold -> CUSTOMS_HOLD
            $normalizedType = strtoupper(str_replace(' ', '_', $rawType));
            if (!in_array($normalizedType, self::SUPPORTED_TYPES, true)) {
                throw ValidationException::withMessages([
                    'type' => [
                        "Invalid exception type '{$rawType}'. Supported types: " . implode(', ', self::SUPPORTED_TYPES) . "."
                    ]
                ]);
            }
            $rawType = $normalizedType;
        }

        $severity = strtoupper(trim($data['severity'] ?? 'MEDIUM'));
        if (!in_array($severity, ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true)) {
            $severity = 'MEDIUM';
        }

        $detectedAt = !empty($data['detected_at']) ? Carbon::parse($data['detected_at']) : now();

        return DB::transaction(function () use ($shipment, $data, $actor, $rawType, $severity, $detectedAt) {
            $exception = ShipmentException::create([
                'shipment_id' => $shipment->id,
                'type' => $rawType,
                'severity' => $severity,
                'status' => ShipmentException::STATUS_OPEN,
                'description' => $data['description'],
                'detected_at' => $detectedAt,
                'resolved_at' => null,
                'reported_by' => $actor?->id,
            ]);

            $oldStatus = $shipment->current_status;

            // Update shipment's operational status
            $shipment->current_status = Shipment::STATUS_EXCEPTION;

            // If estimated delay hours provided, adjust estimated arrival and evaluate delay
            if (!empty($data['estimated_delay_hours'])) {
                $additionalHours = (int) $data['estimated_delay_hours'];
                $baseArrival = $shipment->estimated_arrival ?: $shipment->expected_arrival;
                $newEta = Carbon::parse($baseArrival)->addHours($additionalHours);
                $this->etaService->applyDelayAssessment($shipment, $newEta);
            } else {
                // Severe incidents automatically flag delay
                if (in_array($severity, ['HIGH', 'CRITICAL', 'MEDIUM'])) {
                    $shipment->is_delayed = true;
                    $shipment->delay_severity = $severity;
                    if ($shipment->delay_hours < 24) {
                        $shipment->delay_hours = ($severity === 'CRITICAL' || $severity === 'HIGH') ? 48 : 24;
                    }
                    $shipment->delay_reason = "Flagged due to {$severity} severity {$rawType} exception.";
                    $shipment->save();
                }
            }

            // Append historical tracking event to reflect incident on the timeline
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => Shipment::STATUS_EXCEPTION,
                'event_type' => strtolower($rawType),
                'event_time' => $detectedAt,
                'location_id' => $data['location_id'] ?? $shipment->latestLocationEvent()?->location_id ?? $shipment->origin_location_id,
                'latitude' => $data['latitude'] ?? $shipment->latestLocationEvent()?->latitude,
                'longitude' => $data['longitude'] ?? $shipment->latestLocationEvent()?->longitude,
                'description' => "[EXCEPTION REPORTED] {$rawType} (Severity: {$severity}): {$exception->description}",
                'created_by' => $actor?->id,
            ]);

            // Notify Customer Consignee
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Alert: {$rawType} on Shipment #{$shipment->shipment_number}",
                'message' => "An operational incident [{$rawType}] with severity {$severity} was reported: {$exception->description}",
                'read_at' => null,
            ]);

            // Notify Logistics Operators & Admins
            $staff = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])->get();
            foreach ($staff as $member) {
                if ($actor && $member->id === $actor->id) {
                    continue;
                }
                Notification::create([
                    'user_id' => $member->id,
                    'shipment_id' => $shipment->id,
                    'title' => "[Staff Alert] New Incident on #{$shipment->shipment_number}",
                    'message' => "Incident [{$rawType}] (Severity: {$severity}) logged by " . ($actor?->name ?? 'System'),
                    'read_at' => null,
                ]);
            }

            // Audit Trail
            AuditLog::log(
                $actor?->id,
                'report_exception',
                'ShipmentException',
                $exception->id,
                ['shipment_status' => $oldStatus],
                [
                    'type' => $exception->type,
                    'severity' => $exception->severity,
                    'status' => $exception->status,
                    'description' => $exception->description,
                    'shipment_status' => $shipment->current_status,
                ]
            );

            return $exception;
        });
    }

    /**
     * Advance exception status to INVESTIGATING.
     *
     * @param ShipmentException $exception
     * @param array $data
     * @param User|null $actor
     * @return ShipmentException
     * @throws ValidationException
     */
    public function startInvestigation(ShipmentException $exception, array $data, ?User $actor = null): ShipmentException
    {
        $currentStatus = strtoupper(trim($exception->status));
        if ($currentStatus !== ShipmentException::STATUS_OPEN && $currentStatus !== 'OPEN') {
            throw ValidationException::withMessages([
                'status' => ["Cannot start investigation on an exception with status '{$exception->status}'."]
            ]);
        }

        return DB::transaction(function () use ($exception, $data, $actor) {
            $oldValues = $exception->toArray();
            $investigationNotes = $data['investigation_notes'] ?? 'Active inquiry and mitigation underway by operations desk.';

            $exception->status = ShipmentException::STATUS_INVESTIGATING;
            $exception->description .= "\n\n[INVESTIGATION — " . now()->format('Y-m-d H:i') . " by " . ($actor?->name ?? 'Operations Desk') . "]:\n" . $investigationNotes;
            $exception->save();

            $shipment = $exception->shipment;

            // Append historical tracking event to reflect investigation status on timeline
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => Shipment::STATUS_EXCEPTION,
                'event_type' => 'investigation_active',
                'event_time' => now(),
                'location_id' => $shipment->latestLocationEvent()?->location_id ?? $shipment->origin_location_id,
                'latitude' => $shipment->latestLocationEvent()?->latitude,
                'longitude' => $shipment->latestLocationEvent()?->longitude,
                'description' => "[INVESTIGATION IN PROGRESS] {$exception->type}: {$investigationNotes}",
                'created_by' => $actor?->id,
            ]);

            // Notify Customer Consignee
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Incident Investigating: #{$shipment->shipment_number}",
                'message' => "Our logistics team is actively investigating incident [{$exception->type}]: {$investigationNotes}",
                'read_at' => null,
            ]);

            // Audit Trail
            AuditLog::log(
                $actor?->id,
                'investigate_exception',
                'ShipmentException',
                $exception->id,
                $oldValues,
                $exception->toArray()
            );

            return $exception;
        });
    }

    /**
     * Mark an exception as RESOLVED and restore shipment progression if cleared.
     *
     * @param ShipmentException $exception
     * @param array $resolutionData
     * @param User|null $actor
     * @return ShipmentException
     */
    public function resolveException(ShipmentException $exception, array $resolutionData, ?User $actor = null): ShipmentException
    {
        return DB::transaction(function () use ($exception, $resolutionData, $actor) {
            $oldValues = $exception->toArray();

            $exception->status = ShipmentException::STATUS_RESOLVED;
            $exception->resolved_at = now();
            $notes = $resolutionData['resolution_notes'] ?? 'Incident resolved by operations staff.';
            $exception->description .= "\n\n[RESOLVED — " . now()->format('Y-m-d H:i') . " by " . ($actor?->name ?? 'Staff') . "]:\n" . $notes;
            $exception->save();

            $shipment = $exception->shipment;

            // Check if any open or investigating exceptions remain
            $remainingPending = $shipment->openExceptions()->count();
            if ($remainingPending === 0 && $shipment->current_status === Shipment::STATUS_EXCEPTION) {
                // Restore status to IN_TRANSIT
                $shipment->current_status = Shipment::STATUS_IN_TRANSIT;

                // Re-evaluate delay after resolution
                $assessment = $this->etaService->evaluateDelay($shipment);
                $shipment->is_delayed = $assessment['is_delayed'];
                $shipment->delay_hours = $assessment['delay_hours'];
                $shipment->delay_severity = $assessment['delay_severity'];
                $shipment->delay_reason = $assessment['delay_reason'];

                $shipment->save();
            }

            // Append historical tracking event to reflect resolution on timeline
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $shipment->current_status,
                'event_type' => 'exception_resolved',
                'event_time' => now(),
                'location_id' => $shipment->latestLocationEvent()?->location_id ?? $shipment->destination_location_id,
                'latitude' => $shipment->latestLocationEvent()?->latitude,
                'longitude' => $shipment->latestLocationEvent()?->longitude,
                'description' => "[EXCEPTION RESOLVED] {$exception->type} cleared: {$notes}",
                'created_by' => $actor?->id,
            ]);

            // Notify Customer Consignee
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Resolved: Exception on #{$shipment->shipment_number}",
                'message' => "The incident [{$exception->type}] has been successfully resolved. Consignment moving forward.",
                'read_at' => null,
            ]);

            // Audit Trail
            AuditLog::log(
                $actor?->id,
                'resolve_exception',
                'ShipmentException',
                $exception->id,
                $oldValues,
                $exception->toArray()
            );

            return $exception;
        });
    }

    /**
     * Update an exception (PATCH /api/exceptions/{id}).
     * Seamlessly routes lifecycle status updates (INVESTIGATING, RESOLVED)
     * through canonical lifecycle handlers or updates metadata with audit trail.
     *
     * @param ShipmentException $exception
     * @param array $data
     * @param User|null $actor
     * @return ShipmentException
     */
    public function updateException(ShipmentException $exception, array $data, ?User $actor = null): ShipmentException
    {
        $targetStatus = !empty($data['status']) ? strtoupper(trim($data['status'])) : null;

        // If transitioning to INVESTIGATING
        if ($targetStatus === ShipmentException::STATUS_INVESTIGATING) {
            return $this->startInvestigation($exception, $data, $actor);
        }

        // If transitioning to RESOLVED
        if ($targetStatus === ShipmentException::STATUS_RESOLVED) {
            return $this->resolveException($exception, $data, $actor);
        }

        // Otherwise metadata/field update
        return DB::transaction(function () use ($exception, $data, $actor) {
            $oldValues = $exception->toArray();

            if (!empty($data['severity'])) {
                $severity = strtoupper(trim($data['severity']));
                if (in_array($severity, ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'], true)) {
                    $exception->severity = $severity;
                }
            }

            if (!empty($data['description'])) {
                $exception->description = $data['description'];
            }

            $exception->save();

            AuditLog::log(
                $actor?->id,
                'update_exception',
                'ShipmentException',
                $exception->id,
                $oldValues,
                $exception->toArray()
            );

            return $exception;
        });
    }
}

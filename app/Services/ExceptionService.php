<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    /**
     * Log a new exception for a shipment and adjust shipment status.
     */
    public function logException(Shipment $shipment, array $data, ?User $actor = null): ShipmentException
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $exception = ShipmentException::create([
                'shipment_id' => $shipment->id,
                'type' => $data['type'],
                'severity' => $data['severity'] ?? 'medium',
                'status' => 'open',
                'description' => $data['description'],
                'detected_at' => $data['detected_at'] ?? now(),
                'resolved_at' => null,
                'reported_by' => $actor?->id,
            ]);

            $oldStatus = $shipment->current_status;

            // Escalate shipment status and delay indicators if severe
            if (in_array($exception->severity, ['high', 'critical'])) {
                $shipment->current_status = 'exception';
                $shipment->is_delayed = true;
                $shipment->delay_severity = $exception->severity;
                if ($shipment->delay_hours < 24) {
                    $shipment->delay_hours = $exception->severity === 'critical' ? 72 : 36;
                }
                $shipment->save();
            }

            // Notify Customer
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Alert: Exception Detected on #{$shipment->shipment_number}",
                'message' => "An incident of type '{$exception->type}' (Severity: {$exception->severity}) was reported: {$exception->description}",
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
                    'title' => "[Staff Alert] New Exception #{$shipment->shipment_number}",
                    'message' => "Exception [{$exception->type}] reported by " . ($actor?->name ?? 'System'),
                ]);
            }

            // Audit log
            AuditLog::log(
                $actor?->id,
                'report_exception',
                'ShipmentException',
                $exception->id,
                ['shipment_status' => $oldStatus],
                [
                    'type' => $exception->type,
                    'severity' => $exception->severity,
                    'description' => $exception->description,
                    'shipment_status' => $shipment->current_status,
                ]
            );

            return $exception;
        });
    }

    /**
     * Mark an exception as resolved and update shipment state if all exceptions cleared.
     */
    public function resolveException(ShipmentException $exception, array $resolutionData, ?User $actor = null): ShipmentException
    {
        return DB::transaction(function () use ($exception, $resolutionData, $actor) {
            $oldValues = $exception->toArray();

            $exception->status = 'resolved';
            $exception->resolved_at = now();
            if (!empty($resolutionData['resolution_notes'])) {
                $exception->description .= "\n\n[Resolution Note]: " . $resolutionData['resolution_notes'];
            }
            $exception->save();

            $shipment = $exception->shipment;

            // Check if any open exceptions remain
            $remainingOpen = $shipment->openExceptions()->count();
            if ($remainingOpen === 0 && $shipment->current_status === 'exception') {
                $shipment->current_status = 'in_transit';
                $shipment->save();
            }

            // Notify Customer
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Resolved: Exception on #{$shipment->shipment_number}",
                'message' => "The exception ({$exception->type}) has been resolved. Shipment is moving forward.",
            ]);

            // Audit Log
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
}

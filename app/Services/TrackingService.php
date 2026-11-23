<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    public function __construct(
        protected EtaService $etaService,
        protected ShipmentService $shipmentService
    ) {}

    /**
     * Add a historical tracking checkpoint event and synchronize shipment status.
     * Guaranteed append-only: does not overwrite existing historical events.
     *
     * @param Shipment $shipment
     * @param array $data
     * @param User|null $actor
     * @return TrackingEvent
     * @throws ValidationException
     */
    public function addTrackingEvent(Shipment $shipment, array $data, ?User $actor = null): TrackingEvent
    {
        // 1. Parse and validate event time
        $eventTime = !empty($data['event_time']) ? Carbon::parse($data['event_time']) : now();

        // Prevent events far in the future
        if ($eventTime->greaterThan(now()->addMinutes(15))) {
            throw ValidationException::withMessages([
                'event_time' => ['Event timestamp cannot be in the future.'],
            ]);
        }

        // Validate historical chronological ordering against existing events
        $latestEvent = $shipment->trackingEvents()->latest('event_time')->first();
        if ($latestEvent && $eventTime->lessThan($latestEvent->event_time)) {
            throw ValidationException::withMessages([
                'event_time' => [
                    "Event timestamp ({$eventTime->format('Y-m-d H:i')}) cannot be earlier than the most recent recorded event ({$latestEvent->event_time->format('Y-m-d H:i')}). Historical tracking events must maintain chronological order."
                ],
            ]);
        }

        // 2. Validate location and coordinates
        $locationId = !empty($data['location_id']) ? (int) $data['location_id'] : null;
        $latitude = isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null;

        // Auto-fill coordinates from Location node if coordinates not supplied
        if ($locationId && ($latitude === null || $longitude === null)) {
            $loc = Location::find($locationId);
            if ($loc) {
                $latitude = $latitude ?? $loc->latitude;
                $longitude = $longitude ?? $loc->longitude;
            }
        }

        // 3. Status transition validation
        $oldStatus = strtolower(trim($shipment->current_status));
        $newStatus = !empty($data['status']) ? strtolower(trim($data['status'])) : $oldStatus;

        if ($newStatus !== $oldStatus) {
            $allowReversal = !empty($data['allow_terminal_reversal']);
            $reversalReason = $data['reversal_reason'] ?? null;

            $this->shipmentService->validateStatusTransition(
                $oldStatus,
                $newStatus,
                $actor,
                $allowReversal,
                $reversalReason
            );
        }

        return DB::transaction(function () use (
            $shipment,
            $data,
            $actor,
            $eventTime,
            $locationId,
            $latitude,
            $longitude,
            $oldStatus,
            $newStatus
        ) {
            // Append new historical tracking event (never overwriting prior records)
            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $newStatus,
                'event_type' => $data['event_type'] ?? 'checkpoint',
                'event_time' => $eventTime,
                'location_id' => $locationId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'description' => $data['description'] ?? 'Waypoint checkpoint logged.',
                'created_by' => $actor?->id,
            ]);

            // Synchronize shipment status & delay parameters
            $statusChanged = ($newStatus !== $oldStatus);
            $shipment->current_status = $newStatus;

            if (!empty($data['estimated_arrival'])) {
                $assessment = $this->etaService->evaluateDelay($shipment, $data['estimated_arrival']);
                $shipment->is_delayed = $assessment['is_delayed'];
                $shipment->delay_hours = $assessment['delay_hours'];
                $shipment->delay_severity = $assessment['delay_severity'];
                $shipment->estimated_arrival = $assessment['estimated_arrival'];
            } elseif ($newStatus === Shipment::STATUS_DELIVERED) {
                $shipment->is_delayed = false;
                $shipment->delay_severity = 'none';
            } elseif ($newStatus === Shipment::STATUS_EXCEPTION) {
                $shipment->is_delayed = true;
                if ($shipment->delay_hours === 0) {
                    $shipment->delay_hours = 24;
                    $shipment->delay_severity = 'medium';
                }
            }

            $shipment->save();

            // Notify Customer Consignee
            $title = $statusChanged
                ? "Shipment #{$shipment->shipment_number}: Status -> " . strtoupper($newStatus)
                : "Tracking Update: Shipment #{$shipment->shipment_number}";

            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => $title,
                'message' => ($statusChanged ? "Status progressed from " . strtoupper($oldStatus) . " to " . strtoupper($newStatus) . ". " : "") . $event->description,
                'read_at' => null,
            ]);

            // Audit Log
            AuditLog::log(
                $actor?->id,
                $statusChanged ? 'status_transition' : 'add_tracking_event',
                'Shipment',
                $shipment->id,
                ['current_status' => $oldStatus],
                [
                    'current_status' => $newStatus,
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_time' => $event->event_time->toIso8601String(),
                    'location_id' => $locationId,
                    'coordinates' => ($latitude && $longitude) ? "{$latitude},{$longitude}" : null,
                    'description' => $event->description,
                ]
            );

            // Log special audit if terminal status was reversed
            if ($oldStatus === Shipment::STATUS_DELIVERED && $statusChanged) {
                AuditLog::log(
                    $actor?->id,
                    'terminal_status_reversal',
                    'Shipment',
                    $shipment->id,
                    ['previous_status' => $oldStatus],
                    [
                        'reverted_to' => $newStatus,
                        'reason' => $data['reversal_reason'] ?? 'Administrative correction',
                    ]
                );
            }

            return $event;
        });
    }

    /**
     * Retrieve chronological historical tracking timeline for a shipment.
     */
    public function getTimeline(Shipment $shipment): Collection
    {
        return $shipment->trackingEvents()
            ->with(['location', 'creator'])
            ->orderBy('event_time', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    public function __construct(
        protected EtaService $etaService
    ) {}

    /**
     * Add a tracking checkpoint event and synchronize shipment status.
     */
    public function addTrackingEvent(Shipment $shipment, array $data, ?User $actor = null): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $data['status'],
                'event_type' => $data['event_type'] ?? 'checkpoint',
                'event_time' => $data['event_time'] ?? now(),
                'location_id' => $data['location_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $actor?->id,
            ]);

            $oldStatus = $shipment->current_status;
            $newStatus = $data['status'];

            // Update shipment's current status
            $shipment->current_status = $newStatus;

            // If new estimated arrival provided, evaluate delay
            if (!empty($data['estimated_arrival'])) {
                $assessment = $this->etaService->evaluateDelay($shipment, $data['estimated_arrival']);
                $shipment->is_delayed = $assessment['is_delayed'];
                $shipment->delay_hours = $assessment['delay_hours'];
                $shipment->delay_severity = $assessment['delay_severity'];
                $shipment->estimated_arrival = $assessment['estimated_arrival'];
            } elseif ($newStatus === 'delayed') {
                $shipment->is_delayed = true;
                if ($shipment->delay_hours === 0) {
                    $shipment->delay_hours = 24;
                    $shipment->delay_severity = 'medium';
                }
            } elseif ($newStatus === 'delivered') {
                $shipment->is_delayed = false;
                $shipment->delay_severity = 'none';
            }

            $shipment->save();

            // Dispatch customer notification
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Shipment #{$shipment->shipment_number} Status Updated",
                'message' => "Your shipment status changed from '{$oldStatus}' to '{$newStatus}'. " . ($data['description'] ?? ''),
                'read_at' => null,
            ]);

            // Audit log
            AuditLog::log(
                $actor?->id,
                'create_tracking_event',
                'Shipment',
                $shipment->id,
                ['current_status' => $oldStatus],
                [
                    'current_status' => $newStatus,
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'description' => $event->description,
                ]
            );

            return $event;
        });
    }

    /**
     * Retrieve chronological tracking events for shipment.
     */
    public function getTimeline(Shipment $shipment): Collection
    {
        return $shipment->trackingEvents()
            ->with(['location', 'creator'])
            ->orderBy('event_time', 'asc')
            ->get();
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShipmentService
{
    public function __construct(
        protected EtaService $etaService
    ) {}

    /**
     * Query shipments scoped by role and applied filters.
     */
    public function listShipments(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Shipment::query()
            ->with(['customer', 'carrier', 'originLocation', 'destinationLocation', 'openExceptions'])
            ->withCount(['items', 'trackingEvents', 'openExceptions']);

        // Role authorization scoping
        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('current_status', $filters['status']);
        }

        // Delay filter
        if (isset($filters['is_delayed']) && $filters['is_delayed'] !== '') {
            $query->where('is_delayed', filter_var($filters['is_delayed'], FILTER_VALIDATE_BOOLEAN));
        }

        // Carrier filter
        if (!empty($filters['carrier_id'])) {
            $query->where('carrier_id', $filters['carrier_id']);
        }

        // Search filter (number or cargo type)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('cargo_type', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new shipment with items, initial tracking event, and audit trail.
     */
    public function createShipment(array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($data, $actor) {
            $shipmentNumber = $data['shipment_number'] ?? 'CT-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // Evaluate initial delay if estimated arrival provided
            $expectedArrival = $data['expected_arrival'];
            $estimatedArrival = $data['estimated_arrival'] ?? $expectedArrival;

            $dummy = new Shipment([
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $estimatedArrival,
                'current_status' => $data['current_status'] ?? 'booked',
            ]);
            $delayAssessment = $this->etaService->evaluateDelay($dummy, $estimatedArrival);

            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'customer_id' => $data['customer_id'],
                'carrier_id' => $data['carrier_id'],
                'origin_location_id' => $data['origin_location_id'],
                'destination_location_id' => $data['destination_location_id'],
                'cargo_type' => $data['cargo_type'],
                'weight' => $data['weight'] ?? 0,
                'volume' => $data['volume'] ?? 0,
                'expected_departure' => $data['expected_departure'],
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $estimatedArrival,
                'current_status' => $data['current_status'] ?? 'booked',
                'is_delayed' => $delayAssessment['is_delayed'],
                'delay_hours' => $delayAssessment['delay_hours'],
                'delay_severity' => $delayAssessment['delay_severity'],
            ]);

            // Save items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    if (!empty($itemData['item_name'])) {
                        $shipment->items()->create([
                            'item_name' => $itemData['item_name'],
                            'quantity' => $itemData['quantity'] ?? 1,
                            'weight' => $itemData['weight'] ?? 0,
                            'description' => $itemData['description'] ?? null,
                        ]);
                    }
                }
            }

            // Create initial tracking event
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $shipment->current_status,
                'event_type' => 'booking_created',
                'event_time' => now(),
                'location_id' => $shipment->origin_location_id,
                'description' => 'Shipment booking registered in CargoTrace system.',
                'created_by' => $actor?->id,
            ]);

            // Notify Customer
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Shipment #{$shipment->shipment_number} Booked",
                'message' => "Your shipment #{$shipment->shipment_number} has been created and scheduled for departure.",
            ]);

            // Audit Log
            AuditLog::log(
                $actor?->id,
                'create_shipment',
                'Shipment',
                $shipment->id,
                null,
                $shipment->toArray()
            );

            return $shipment;
        });
    }

    /**
     * Update an existing shipment.
     */
    public function updateShipment(Shipment $shipment, array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $oldValues = $shipment->toArray();

            $shipment->fill($data);

            // Re-evaluate delay
            $assessment = $this->etaService->evaluateDelay($shipment, $shipment->estimated_arrival);
            $shipment->is_delayed = $assessment['is_delayed'];
            $shipment->delay_hours = $assessment['delay_hours'];
            $shipment->delay_severity = $assessment['delay_severity'];

            $shipment->save();

            // Sync items if passed
            if (isset($data['items']) && is_array($data['items'])) {
                $shipment->items()->delete();
                foreach ($data['items'] as $item) {
                    if (!empty($item['item_name'])) {
                        $shipment->items()->create([
                            'item_name' => $item['item_name'],
                            'quantity' => $item['quantity'] ?? 1,
                            'weight' => $item['weight'] ?? 0,
                            'description' => $item['description'] ?? null,
                        ]);
                    }
                }
            }

            // Audit Log
            AuditLog::log(
                $actor?->id,
                'update_shipment',
                'Shipment',
                $shipment->id,
                $oldValues,
                $shipment->toArray()
            );

            return $shipment;
        });
    }

    /**
     * Get shipment with all relationships eagerly loaded.
     */
    public function getShipmentWithFullDetails(Shipment $shipment): Shipment
    {
        return $shipment->load([
            'customer',
            'carrier',
            'originLocation',
            'destinationLocation',
            'items',
            'trackingEvents.location',
            'trackingEvents.creator',
            'exceptions.reporter',
        ]);
    }

    /**
     * Summary statistics for dashboard based on user role.
     */
    public function getDashboardMetrics(User $user): array
    {
        $query = Shipment::query();
        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        $total = (clone $query)->count();
        $inTransit = (clone $query)->whereIn('current_status', ['in_transit', 'customs_hold', 'out_for_delivery'])->count();
        $delayed = (clone $query)->where('is_delayed', true)->count();
        $delivered = (clone $query)->where('current_status', 'delivered')->count();

        $exceptionsQuery = ShipmentException::query();
        if ($user->isCustomer()) {
            $exceptionsQuery->whereHas('shipment', function ($q) use ($user) {
                $q->where('customer_id', $user->id);
            });
        }
        $openExceptions = $exceptionsQuery->whereIn('status', ['open', 'under_review'])->count();

        $onTimeRate = $total > 0 ? round((($total - $delayed) / $total) * 100, 1) : 100;

        return [
            'total_shipments' => $total,
            'in_transit' => $inTransit,
            'delayed' => $delayed,
            'delivered' => $delivered,
            'open_exceptions' => $openExceptions,
            'on_time_rate' => $onTimeRate,
        ];
    }
}

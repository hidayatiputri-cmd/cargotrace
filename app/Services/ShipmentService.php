<?php

namespace App\Services;

use App\Exceptions\InvalidStatusTransitionException;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        protected EtaService $etaService
    ) {}

    /**
     * Validate status transition adhering to the shipment lifecycle:
     * CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED
     *
     * Prevents invalid transitions and prevents terminal state (DELIVERED) reversal
     * without special administrative procedure.
     *
     * @param string $currentStatus
     * @param string $targetStatus
     * @param User|null $actor
     * @param bool $allowTerminalReversal
     * @param string|null $reversalReason
     * @return bool
     * @throws InvalidStatusTransitionException
     */
    public function validateStatusTransition(
        string $currentStatus,
        string $targetStatus,
        ?User $actor = null,
        bool $allowTerminalReversal = false,
        ?string $reversalReason = null
    ): bool {
        $from = strtolower(trim($currentStatus));
        $to = strtolower(trim($targetStatus));

        // Same status is a valid checkpoint update
        if ($from === $to) {
            return true;
        }

        // Terminal Status Protection: DELIVERED
        if ($from === Shipment::STATUS_DELIVERED) {
            $isAuthorizedAdmin = $actor && $actor->isAdmin();
            $hasValidReason = !empty($reversalReason) && strlen(trim($reversalReason)) >= 10;

            if (!$isAuthorizedAdmin || !$allowTerminalReversal || !$hasValidReason) {
                throw InvalidStatusTransitionException::terminalLocked($from);
            }

            // Special administrative reversal procedure validated
            return true;
        }

        // Terminal Status: CANCELLED
        if ($from === Shipment::STATUS_CANCELLED) {
            throw InvalidStatusTransitionException::terminalLocked($from);
        }

        // Standard Lifecycle Transition Map Check
        $allowedTransitions = Shipment::getAllowedTransitions($from);

        if (!in_array($to, $allowedTransitions, true)) {
            throw InvalidStatusTransitionException::invalidTransition($from, $to, $allowedTransitions);
        }

        return true;
    }

    /**
     * Query shipments scoped by role and applied filters.
     */
    public function listShipments(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Shipment::query()
            ->with(['customer', 'carrier', 'originLocation', 'destinationLocation', 'openExceptions'])
            ->withCount(['items', 'trackingEvents', 'openExceptions']);

        // Role authorization scoping: Customer strictly isolated to own shipments
        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('current_status', strtolower(trim($filters['status'])));
        }

        // Delay filter
        if (isset($filters['is_delayed']) && $filters['is_delayed'] !== '') {
            $query->where('is_delayed', filter_var($filters['is_delayed'], FILTER_VALIDATE_BOOLEAN));
        }

        // Carrier filter
        if (!empty($filters['carrier_id'])) {
            $query->where('carrier_id', $filters['carrier_id']);
        }

        // Origin location filter
        if (!empty($filters['origin_location_id'])) {
            $query->where('origin_location_id', $filters['origin_location_id']);
        }

        // Destination location filter
        if (!empty($filters['destination_location_id'])) {
            $query->where('destination_location_id', $filters['destination_location_id']);
        }

        // Delay severity filter
        if (!empty($filters['delay_severity'])) {
            $query->where('delay_severity', strtoupper(trim($filters['delay_severity'])));
        }

        // Has open exceptions filter
        if (isset($filters['has_exceptions']) && $filters['has_exceptions'] !== '') {
            $hasExceptions = filter_var($filters['has_exceptions'], FILTER_VALIDATE_BOOLEAN);
            if ($hasExceptions) {
                $query->where(function ($q) {
                    $q->whereHas('openExceptions')
                      ->orWhere('current_status', Shipment::STATUS_EXCEPTION);
                });
            } else {
                $query->whereDoesntHave('openExceptions')
                      ->where('current_status', '!=', Shipment::STATUS_EXCEPTION);
            }
        }

        // Date range: Expected departure
        if (!empty($filters['departure_from'])) {
            $query->whereDate('expected_departure', '>=', $filters['departure_from']);
        }
        if (!empty($filters['departure_to'])) {
            $query->whereDate('expected_departure', '<=', $filters['departure_to']);
        }

        // Date range: Expected or Estimated arrival
        if (!empty($filters['arrival_from'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('expected_arrival', '>=', $filters['arrival_from'])
                  ->orWhereDate('estimated_arrival', '>=', $filters['arrival_from']);
            });
        }
        if (!empty($filters['arrival_to'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereDate('expected_arrival', '<=', $filters['arrival_to'])
                  ->orWhereDate('estimated_arrival', '<=', $filters['arrival_to']);
            });
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
                  })
                  ->orWhereHas('carrier', function ($carQ) use ($search) {
                      $carQ->where('name', 'like', "%{$search}%")
                           ->orWhere('code', 'like', "%{$search}%");
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
            $initialStatus = !empty($data['current_status'])
                ? strtolower(trim($data['current_status']))
                : Shipment::STATUS_CREATED;

            // Evaluate initial delay if estimated arrival provided
            $expectedArrival = $data['expected_arrival'];
            $estimatedArrival = $data['estimated_arrival'] ?? $expectedArrival;

            $dummy = new Shipment([
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $estimatedArrival,
                'current_status' => $initialStatus,
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
                'current_status' => $initialStatus,
                'is_delayed' => $delayAssessment['is_delayed'],
                'delay_hours' => $delayAssessment['delay_hours'],
                'delay_severity' => $delayAssessment['delay_severity'],
                'delay_reason' => $delayAssessment['delay_reason'],
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

            // Create initial historical tracking event
            $initialEventTime = now();
            if (!empty($data['initial_event_time'])) {
                $initialEventTime = Carbon::parse($data['initial_event_time']);
            } elseif (!empty($data['expected_departure']) && Carbon::parse($data['expected_departure'])->lessThan(now())) {
                $initialEventTime = Carbon::parse($data['expected_departure']);
            }

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'status' => $initialStatus,
                'event_type' => ($initialStatus === Shipment::STATUS_CREATED) ? 'shipment_created' : 'booking_registered',
                'event_time' => $initialEventTime,
                'location_id' => $shipment->origin_location_id,
                'description' => "Shipment registered in CargoTrace with initial status " . strtoupper($initialStatus) . ".",
                'created_by' => $actor?->id,
            ]);

            // Notify Customer Consignee
            Notification::create([
                'user_id' => $shipment->customer_id,
                'shipment_id' => $shipment->id,
                'title' => "Shipment #{$shipment->shipment_number} Registered",
                'message' => "Your shipment #{$shipment->shipment_number} has been created with status " . strtoupper($initialStatus) . ". Scheduled departure: " . Carbon::parse($shipment->expected_departure)->format('M d, Y H:i') . ".",
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
            $oldStatus = strtolower(trim($shipment->current_status));

            // Validate status transition if provided and changed
            if (!empty($data['current_status'])) {
                $targetStatus = strtolower(trim($data['current_status']));
                if ($targetStatus !== $oldStatus) {
                    $allowReversal = !empty($data['allow_terminal_reversal']);
                    $reversalReason = $data['reversal_reason'] ?? null;

                    $this->validateStatusTransition(
                        $oldStatus,
                        $targetStatus,
                        $actor,
                        $allowReversal,
                        $reversalReason
                    );

                    $shipment->current_status = $targetStatus;

                    // Append historical tracking event for the status update
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'status' => $targetStatus,
                        'event_type' => 'status_transition',
                        'event_time' => now(),
                        'location_id' => $shipment->origin_location_id,
                        'description' => "Status transitioned from " . strtoupper($oldStatus) . " to " . strtoupper($targetStatus) . ".",
                        'created_by' => $actor?->id,
                    ]);

                    // Notify customer
                    Notification::create([
                        'user_id' => $shipment->customer_id,
                        'shipment_id' => $shipment->id,
                        'title' => "Shipment #{$shipment->shipment_number}: Status -> " . strtoupper($targetStatus),
                        'message' => "Shipment status changed from " . strtoupper($oldStatus) . " to " . strtoupper($targetStatus) . ".",
                    ]);
                }
            }

            // Fill remaining fields
            $shipment->fill(collect($data)->except(['current_status', 'allow_terminal_reversal', 'reversal_reason'])->toArray());

            // Re-evaluate delay
            $assessment = $this->etaService->evaluateDelay($shipment, $shipment->estimated_arrival);
            $shipment->is_delayed = $assessment['is_delayed'];
            $shipment->delay_hours = $assessment['delay_hours'];
            $shipment->delay_severity = $assessment['delay_severity'];
            $shipment->delay_reason = $assessment['delay_reason'];

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
     * Delete a shipment and related records within a transaction, recording audit log.
     */
    public function deleteShipment(Shipment $shipment, ?User $actor = null): bool
    {
        return DB::transaction(function () use ($shipment, $actor) {
            $oldValues = $shipment->toArray();
            $shipmentId = $shipment->id;

            AuditLog::log(
                $actor?->id,
                'delete_shipment',
                'Shipment',
                $shipmentId,
                $oldValues,
                null
            );

            // Cleanly delete child items and relationships
            $shipment->items()->delete();
            $shipment->trackingEvents()->delete();
            $shipment->exceptions()->delete();
            $shipment->notifications()->delete();

            return (bool) $shipment->delete();
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
        $activeShipments = (clone $query)->whereNotIn('current_status', [
            Shipment::STATUS_DELIVERED,
            Shipment::STATUS_CANCELLED
        ])->count();

        $inTransit = (clone $query)->whereIn('current_status', [
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_PICKED_UP,
            'customs_hold',
            'out_for_delivery'
        ])->count();

        $delayed = (clone $query)->where('is_delayed', true)->count();
        $delivered = (clone $query)->where('current_status', Shipment::STATUS_DELIVERED)->count();

        $exceptionsQuery = \App\Models\ShipmentException::query();
        if ($user->isCustomer()) {
            $exceptionsQuery->whereHas('shipment', function ($q) use ($user) {
                $q->where('customer_id', $user->id);
            });
        }
        $openExceptions = $exceptionsQuery->whereIn('status', [
            \App\Models\ShipmentException::STATUS_OPEN,
            \App\Models\ShipmentException::STATUS_INVESTIGATING,
            'open',
            'investigating',
            'under_review'
        ])->count();

        $onTimeRate = $total > 0 ? round((($total - $delayed) / $total) * 100, 1) : 100;

        // Status breakdown distribution
        $statusDistribution = [
            'created' => (clone $query)->where('current_status', Shipment::STATUS_CREATED)->count(),
            'booked' => (clone $query)->where('current_status', Shipment::STATUS_BOOKED)->count(),
            'picked_up' => (clone $query)->where('current_status', Shipment::STATUS_PICKED_UP)->count(),
            'in_transit' => (clone $query)->where('current_status', Shipment::STATUS_IN_TRANSIT)->count(),
            'exception' => (clone $query)->where(function ($q) {
                $q->where('current_status', Shipment::STATUS_EXCEPTION)
                  ->orWhere('current_status', 'customs_hold');
            })->count(),
            'delivered' => $delivered,
        ];

        return [
            'total_shipments' => $total,
            'active_shipments' => $activeShipments,
            'in_transit' => $inTransit,
            'delayed' => $delayed,
            'delivered' => $delivered,
            'open_exceptions' => $openExceptions,
            'on_time_rate' => $onTimeRate,
            'status_distribution' => $statusDistribution,
        ];
    }
}

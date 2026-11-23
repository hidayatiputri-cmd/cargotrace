<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    // Standard lifecycle status constants
    public const STATUS_CREATED = 'created';
    public const STATUS_BOOKED = 'booked';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_EXCEPTION = 'exception';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shipment_number',
        'customer_id',
        'carrier_id',
        'origin_location_id',
        'destination_location_id',
        'cargo_type',
        'weight',
        'volume',
        'expected_departure',
        'expected_arrival',
        'estimated_arrival',
        'current_status',
        'is_delayed',
        'delay_hours',
        'delay_severity',
        'delay_reason',
    ];

    protected function casts(): array
    {
        return [
            'expected_departure' => 'datetime',
            'expected_arrival' => 'datetime',
            'estimated_arrival' => 'datetime',
            'is_delayed' => 'boolean',
            'weight' => 'decimal:2',
            'volume' => 'decimal:2',
            'delay_hours' => 'integer',
        ];
    }

    /**
     * Map of valid status transitions adhering to:
     * CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> DELIVERED
     */
    public static function getAllowedTransitions(string $status): array
    {
        $status = strtolower(trim($status));

        $transitionMap = [
            self::STATUS_CREATED => [
                self::STATUS_BOOKED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_BOOKED => [
                self::STATUS_PICKED_UP,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_PICKED_UP => [
                self::STATUS_IN_TRANSIT,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_IN_TRANSIT => [
                self::STATUS_DELIVERED,
                self::STATUS_EXCEPTION,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_EXCEPTION => [
                self::STATUS_IN_TRANSIT,
                self::STATUS_DELIVERED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_DELIVERED => [], // Terminal state
            self::STATUS_CANCELLED => [], // Terminal state
        ];

        return $transitionMap[$status] ?? [];
    }

    /**
     * Check if a status is terminal.
     */
    public static function isTerminal(string $status): bool
    {
        $normalized = strtolower(trim($status));
        return in_array($normalized, [self::STATUS_DELIVERED, self::STATUS_CANCELLED], true);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_time', 'asc')->orderBy('id', 'asc');
    }

    public function latestTrackingEvent(): HasOne
    {
        return $this->hasOne(TrackingEvent::class)->latestOfMany('event_time');
    }

    /**
     * Get the most recent tracking event that contains valid spatial location information.
     */
    public function latestLocationEvent(): ?TrackingEvent
    {
        return $this->trackingEvents()
            ->where(function ($query) {
                $query->whereNotNull('location_id')
                      ->orWhere(function ($coordQ) {
                          $coordQ->whereNotNull('latitude')->whereNotNull('longitude');
                      });
            })
            ->latest('event_time')
            ->first();
    }

    /**
     * Accessor for human-readable current position description.
     */
    public function getCurrentLocationDescriptionAttribute(): string
    {
        $event = $this->latestLocationEvent();

        if ($event) {
            if ($event->location) {
                return "{$event->location->name} ({$event->location->code}) — {$event->location->city}, {$event->location->country}";
            }

            if ($event->latitude !== null && $event->longitude !== null) {
                return "Maritime / Air Corridor (Lat: " . number_format($event->latitude, 4) . ", Lng: " . number_format($event->longitude, 4) . ")";
            }
        }

        if ($this->originLocation) {
            return "At Origin: {$this->originLocation->name} ({$this->originLocation->code})";
        }

        return "Location Pending Initial Dispatch";
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class)->orderBy('detected_at', 'desc');
    }

    public function openExceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class)
            ->whereIn('status', [
                ShipmentException::STATUS_OPEN,
                ShipmentException::STATUS_INVESTIGATING,
                'open',
                'under_review',
                'investigating'
            ]);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}

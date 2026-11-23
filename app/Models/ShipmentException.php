<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentException extends Model
{
    use HasFactory;

    // Supported Exception Types
    public const TYPE_CUSTOMS_HOLD = 'CUSTOMS_HOLD';
    public const TYPE_WEATHER_DELAY = 'WEATHER_DELAY';
    public const TYPE_PORT_CONGESTION = 'PORT_CONGESTION';
    public const TYPE_DAMAGED = 'DAMAGED';
    public const TYPE_MISROUTED = 'MISROUTED';
    public const TYPE_DOCUMENTATION_ISSUE = 'DOCUMENTATION_ISSUE';

    // Supported Lifecycle Statuses: OPEN -> INVESTIGATING -> RESOLVED
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_INVESTIGATING = 'INVESTIGATING';
    public const STATUS_RESOLVED = 'RESOLVED';

    protected $fillable = [
        'shipment_id',
        'type',
        'severity',
        'status',
        'description',
        'detected_at',
        'resolved_at',
        'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function isOpen(): bool
    {
        return strtoupper(trim($this->status)) === self::STATUS_OPEN;
    }

    public function isInvestigating(): bool
    {
        return strtoupper(trim($this->status)) === self::STATUS_INVESTIGATING;
    }

    public function isResolved(): bool
    {
        return strtoupper(trim($this->status)) === self::STATUS_RESOLVED;
    }

    public function isPending(): bool
    {
        return in_array(strtoupper(trim($this->status)), [self::STATUS_OPEN, self::STATUS_INVESTIGATING], true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id',
        'status',
        'event_type',
        'event_time',
        'location_id',
        'latitude',
        'longitude',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Determine if this historical tracking event represents an exception or incident.
     */
    public function isExceptionEvent(): bool
    {
        $exceptionStatuses = ['exception', 'delayed', 'customs_hold'];
        $exceptionTypes = [
            'weather_alert',
            'customs_hold',
            'cargo_damage',
            'course_deviation',
            'incident',
            'emergency',
            'exception',
            'documentation_hold'
        ];

        return in_array(strtolower($this->status), $exceptionStatuses, true)
            || in_array(strtolower($this->event_type), $exceptionTypes, true);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}

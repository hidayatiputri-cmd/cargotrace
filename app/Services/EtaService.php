<?php

namespace App\Services;

use App\Models\Shipment;
use Carbon\Carbon;

class EtaService
{
    /**
     * Calculate and return delay metrics based on expected arrival and estimated arrival.
     *
     * @param Shipment $shipment
     * @param Carbon|string|null $newEstimatedArrival
     * @return array{is_delayed: bool, delay_hours: int, delay_severity: string, estimated_arrival: Carbon|null}
     */
    public function evaluateDelay(Shipment $shipment, Carbon|string|null $newEstimatedArrival = null): array
    {
        $estimated = $newEstimatedArrival ? Carbon::parse($newEstimatedArrival) : $shipment->estimated_arrival;
        $expected = $shipment->expected_arrival ? Carbon::parse($shipment->expected_arrival) : null;

        if (!$expected) {
            return [
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'none',
                'estimated_arrival' => $estimated,
            ];
        }

        // Target timestamp to compare against expected
        $targetArrival = $estimated ?: ($shipment->current_status !== 'delivered' && now()->greaterThan($expected) ? now() : null);

        if ($targetArrival && $targetArrival->greaterThan($expected)) {
            $diffMinutes = abs($targetArrival->diffInMinutes($expected));
            $delayHours = (int) ceil($diffMinutes / 60);
            $severity = $this->determineSeverity($delayHours);

            return [
                'is_delayed' => true,
                'delay_hours' => $delayHours,
                'delay_severity' => $severity,
                'estimated_arrival' => $estimated ?: $targetArrival,
            ];
        }

        return [
            'is_delayed' => false,
            'delay_hours' => 0,
            'delay_severity' => 'none',
            'estimated_arrival' => $estimated,
        ];
    }

    /**
     * Map delay hours to severity tier.
     */
    public function determineSeverity(int $hours): string
    {
        if ($hours <= 0) {
            return 'none';
        }
        if ($hours <= 12) {
            return 'low';
        }
        if ($hours <= 36) {
            return 'medium';
        }
        if ($hours <= 72) {
            return 'high';
        }

        return 'critical';
    }

    /**
     * Apply delay evaluation and save to the shipment model.
     */
    public function applyDelayAssessment(Shipment $shipment, Carbon|string|null $newEstimatedArrival = null): Shipment
    {
        $assessment = $this->evaluateDelay($shipment, $newEstimatedArrival);

        $shipment->is_delayed = $assessment['is_delayed'];
        $shipment->delay_hours = $assessment['delay_hours'];
        $shipment->delay_severity = $assessment['delay_severity'];
        if ($assessment['estimated_arrival']) {
            $shipment->estimated_arrival = $assessment['estimated_arrival'];
        }

        $shipment->save();

        return $shipment;
    }
}

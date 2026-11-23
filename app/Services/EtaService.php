<?php

namespace App\Services;

use App\Models\Shipment;
use Carbon\Carbon;

class EtaService
{
    /**
     * Calculate and return deterministic delay metrics based on expected arrival,
     * estimated arrival, and configurable delay threshold.
     *
     * Rules:
     * - Threshold defaults to config('cargotrace.delay_threshold_hours', 12).
     * - Delayed if total delay hours > threshold.
     * - Severity:
     *   * LOW: 0-6 hours beyond threshold
     *   * MEDIUM: >6-24 hours beyond threshold
     *   * HIGH: >24 hours beyond threshold
     *
     * @param Shipment $shipment
     * @param Carbon|string|null $newEstimatedArrival
     * @param int|null $customThreshold
     * @return array{is_delayed: bool, delay_hours: int, delay_severity: string, delay_reason: string, estimated_arrival: Carbon|null, excess_hours: int, threshold_hours: int}
     */
    public function evaluateDelay(
        Shipment $shipment,
        Carbon|string|null $newEstimatedArrival = null,
        ?int $customThreshold = null
    ): array {
        $threshold = $customThreshold ?? (int) config('cargotrace.delay_threshold_hours', 12);
        $estimated = $newEstimatedArrival ? Carbon::parse($newEstimatedArrival) : $shipment->estimated_arrival;
        $expected = $shipment->expected_arrival ? Carbon::parse($shipment->expected_arrival) : null;

        if (!$expected) {
            return [
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'NONE',
                'delay_reason' => 'Expected arrival date is not defined.',
                'estimated_arrival' => $estimated,
                'excess_hours' => 0,
                'threshold_hours' => $threshold,
            ];
        }

        // Target timestamp to compare against expected arrival
        $targetArrival = $estimated ?: ($shipment->current_status !== 'delivered' && now()->greaterThan($expected) ? now() : null);

        if ($targetArrival && $targetArrival->greaterThan($expected)) {
            $diffSeconds = abs($targetArrival->copy()->floorSecond()->diffInSeconds($expected->copy()->floorSecond()));
            $totalDelayHours = (int) ceil($diffSeconds / 3600);

            // Check if delay exceeds SLA threshold
            if ($totalDelayHours > $threshold) {
                $excessHours = $totalDelayHours - $threshold;
                $severity = $this->determineSeverity($excessHours);
                $reason = "Exceeds {$threshold}h SLA threshold by {$excessHours}h (Total delay: {$totalDelayHours}h). Severity: {$severity}.";

                return [
                    'is_delayed' => true,
                    'delay_hours' => $totalDelayHours,
                    'delay_severity' => $severity,
                    'delay_reason' => $reason,
                    'estimated_arrival' => $estimated ?: $targetArrival,
                    'excess_hours' => $excessHours,
                    'threshold_hours' => $threshold,
                ];
            }

            // Delay exists but is within permitted SLA buffer threshold
            return [
                'is_delayed' => false,
                'delay_hours' => $totalDelayHours,
                'delay_severity' => 'NONE',
                'delay_reason' => "Delay of {$totalDelayHours}h is within allowed SLA threshold ({$threshold}h).",
                'estimated_arrival' => $estimated ?: $targetArrival,
                'excess_hours' => 0,
                'threshold_hours' => $threshold,
            ];
        }

        // On time or early
        return [
            'is_delayed' => false,
            'delay_hours' => 0,
            'delay_severity' => 'NONE',
            'delay_reason' => 'On schedule. Estimated arrival is on or ahead of expected arrival.',
            'estimated_arrival' => $estimated,
            'excess_hours' => 0,
            'threshold_hours' => $threshold,
        ];
    }

    /**
     * Map excess hours beyond threshold to deterministic severity tier.
     * LOW: 0-6 hours beyond threshold
     * MEDIUM: >6-24 hours beyond threshold
     * HIGH: >24 hours beyond threshold
     */
    public function determineSeverity(int $excessHours): string
    {
        if ($excessHours <= 0) {
            return 'NONE';
        }
        if ($excessHours <= 6) {
            return 'LOW';
        }
        if ($excessHours <= 24) {
            return 'MEDIUM';
        }

        return 'HIGH';
    }

    /**
     * Apply delay evaluation and persist to the shipment model.
     */
    public function applyDelayAssessment(
        Shipment $shipment,
        Carbon|string|null $newEstimatedArrival = null,
        ?int $customThreshold = null
    ): Shipment {
        $assessment = $this->evaluateDelay($shipment, $newEstimatedArrival, $customThreshold);

        $shipment->is_delayed = $assessment['is_delayed'];
        $shipment->delay_hours = $assessment['delay_hours'];
        $shipment->delay_severity = $assessment['delay_severity'];
        $shipment->delay_reason = $assessment['delay_reason'];
        if ($assessment['estimated_arrival']) {
            $shipment->estimated_arrival = $assessment['estimated_arrival'];
        }

        $shipment->save();

        return $shipment;
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

class InvalidStatusTransitionException extends ValidationException
{
    /**
     * Create an exception for an invalid transition between statuses.
     */
    public static function invalidTransition(string $from, string $to, array $allowed): self
    {
        $allowedStr = empty($allowed) ? 'none (terminal state)' : implode(', ', array_map('strtoupper', $allowed));

        return self::withMessages([
            'status' => [
                "Invalid status transition from '" . strtoupper($from) . "' to '" . strtoupper($to) . "'. Allowed next transitions: [{$allowedStr}]."
            ]
        ]);
    }

    /**
     * Create an exception for attempting to reverse a terminal status without special procedure.
     */
    public static function terminalLocked(string $status): self
    {
        return self::withMessages([
            'status' => [
                "Cannot transition from terminal status '" . strtoupper($status) . "'. Delivered shipments are finalized and cannot be reverted without an authorized administrative reversal procedure."
            ]
        ]);
    }
}

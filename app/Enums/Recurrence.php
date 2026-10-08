<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * How often a task repeats. Completing a repeating task creates its next occurrence.
 */
enum Recurrence: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
        };
    }

    /**
     * The first repeat of $from that falls after $today, so a task finished late
     * doesn't come back already overdue.
     */
    public function nextAfter(CarbonInterface $from, CarbonInterface $today): CarbonInterface
    {
        $start = $from->toImmutable()->startOfDay();
        $steps = 0;

        // Count steps from the original date, so a task due on the 31st returns to the 31st
        // instead of drifting to the 30th after a shorter month.
        do {
            $steps++;
            $next = match ($this) {
                self::Daily => $start->addDays($steps),
                self::Weekly => $start->addWeeks($steps),
                self::Monthly => $start->addMonthsNoOverflow($steps),
            };
        } while ($next->lte($today));

        return $next;
    }
}

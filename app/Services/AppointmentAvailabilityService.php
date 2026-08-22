<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Therapist;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

class AppointmentAvailabilityService
{
    public const START_INCREMENT_MINUTES = 15;

    public function availability(Therapist $therapist, DateTimeInterface|string $date, int $durationMinutes): array
    {
        $date = CarbonImmutable::parse($date)->startOfDay();
        $now = CarbonImmutable::now(config('app.timezone'));
        $earliestStart = $date->isSameDay($now) ? $now : null;
        $periods = $this->workPeriodsForDate($therapist, $date);

        if ($durationMinutes < 1 || $periods->isEmpty() || ! $therapist->user_id) {
            return $this->result($date, $durationMinutes, $periods->all(), []);
        }

        $rangeStart = $periods->min(fn (array $period) => $period['start']);
        $rangeEnd = $periods->max(fn (array $period) => $period['end']);
        $busyIntervals = Appointment::query()
            ->with('sessionType:id,duration_minutes')
            ->where('therapist_id', $therapist->user_id)
            ->where('status', '!=', 'ملغى')
            ->where('scheduled_at', '<', $rangeEnd)
            ->where(function ($query) use ($rangeStart) {
                $query->where('end_at', '>', $rangeStart)
                    ->orWhereNull('end_at');
            })
            ->get()
            ->map(function (Appointment $appointment) {
                $start = CarbonImmutable::instance($appointment->scheduled_at);
                $end = $appointment->end_at
                    ? CarbonImmutable::instance($appointment->end_at)
                    : $start->addMinutes((int) ($appointment->sessionType?->duration_minutes ?? 0));

                return ['start' => $start, 'end' => $end];
            })
            ->filter(fn (array $interval) => $interval['end']->greaterThan($interval['start']))
            ->sortBy(fn (array $interval) => $interval['start']->getTimestamp())
            ->values();

        $freeWindows = [];
        foreach ($periods as $period) {
            $cursor = $period['start'];

            foreach ($busyIntervals as $busy) {
                if ($busy['end']->lessThanOrEqualTo($period['start']) || $busy['start']->greaterThanOrEqualTo($period['end'])) {
                    continue;
                }

                $busyStart = $busy['start']->max($period['start']);
                $busyEnd = $busy['end']->min($period['end']);

                if ($busyStart->greaterThan($cursor)) {
                    $freeWindows[] = $this->window($cursor, $busyStart, $durationMinutes, $earliestStart);
                }

                if ($busyEnd->greaterThan($cursor)) {
                    $cursor = $busyEnd;
                }
            }

            if ($cursor->lessThan($period['end'])) {
                $freeWindows[] = $this->window($cursor, $period['end'], $durationMinutes, $earliestStart);
            }
        }

        return $this->result($date, $durationMinutes, $periods->all(), $freeWindows);
    }

    public function isWithinWorkPeriod(Therapist $therapist, DateTimeInterface|string $start, int $durationMinutes): bool
    {
        $start = CarbonImmutable::parse($start);
        if ($durationMinutes < 1 || $start->second !== 0 || $start->minute % self::START_INCREMENT_MINUTES !== 0) {
            return false;
        }

        return $this->fitsWithinWorkPeriod($therapist, $start, $durationMinutes);
    }

    public function fitsWithinWorkPeriod(Therapist $therapist, DateTimeInterface|string $start, int $durationMinutes): bool
    {
        $start = CarbonImmutable::parse($start);
        if ($durationMinutes < 1) {
            return false;
        }

        $end = $start->addMinutes($durationMinutes);

        return $this->workPeriodsForDate($therapist, $start)->contains(
            fn (array $period) => $start->greaterThanOrEqualTo($period['start'])
                && $end->lessThanOrEqualTo($period['end'])
        );
    }

    private function workPeriodsForDate(Therapist $therapist, CarbonImmutable $date): Collection
    {
        return $therapist->workPeriods()
            ->where('weekday', $date->dayOfWeek)
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($period) => [
                'start' => CarbonImmutable::parse($date->toDateString().' '.$period->starts_at),
                'end' => CarbonImmutable::parse($date->toDateString().' '.$period->ends_at),
            ]);
    }

    private function window(
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $durationMinutes,
        ?CarbonImmutable $earliestStart
    ): array {
        $displayStart = $earliestStart
            && $earliestStart->greaterThan($start)
            && $earliestStart->lessThan($end)
                ? $earliestStart
                : $start;
        $candidate = $this->ceilToIncrement($displayStart);
        $starts = [];

        while ($candidate->addMinutes($durationMinutes)->lessThanOrEqualTo($end)) {
            $starts[] = $candidate->format('H:i');
            $candidate = $candidate->addMinutes(self::START_INCREMENT_MINUTES);
        }

        return [
            'start' => $displayStart->format('H:i'),
            'end' => $end->format('H:i'),
            'duration_minutes' => (int) $displayStart->diffInMinutes($end),
            'start_times' => $starts,
        ];
    }

    private function ceilToIncrement(CarbonImmutable $time): CarbonImmutable
    {
        $time = $time->setSecond(0);
        $remainder = $time->minute % self::START_INCREMENT_MINUTES;

        return $remainder === 0
            ? $time
            : $time->addMinutes(self::START_INCREMENT_MINUTES - $remainder);
    }

    private function result(CarbonImmutable $date, int $durationMinutes, array $periods, array $freeWindows): array
    {
        return [
            'date' => $date->toDateString(),
            'duration_minutes' => $durationMinutes,
            'start_increment_minutes' => self::START_INCREMENT_MINUTES,
            'work_periods' => collect($periods)->map(fn (array $period) => [
                'start' => $period['start']->format('H:i'),
                'end' => $period['end']->format('H:i'),
            ])->values()->all(),
            'free_windows' => $freeWindows,
            'has_bookable_times' => collect($freeWindows)->contains(fn (array $window) => $window['start_times'] !== []),
        ];
    }
}

<?php

namespace App\Actions;

use App\Models\Medication;
use App\Models\MedicationSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Create the reminders and doses of the coming hours from the active schedules.
 *
 * Running it many times is safe: existing doses are kept, never duplicated
 * (unique index on medication_schedule_id + scheduled_for).
 */
class GenerateDoses
{
    /**
     * How far ahead doses are created.
     */
    public const HOURS_AHEAD = 48;

    /**
     * Generate the upcoming doses of every active schedule.
     *
     * @return int The number of doses created.
     */
    public function forAllSchedules(?CarbonInterface $now = null): int
    {
        $now = CarbonImmutable::instance($now ?? now());
        $created = 0;

        $this->activeSchedules()->chunkById(200, function ($schedules) use ($now, &$created) {
            foreach ($schedules as $schedule) {
                $created += $this->forSchedule($schedule, $now);
            }
        });

        return $created;
    }

    /**
     * Generate the upcoming doses of one medication (used right after its schedules change).
     *
     * @return int The number of doses created.
     */
    public function forMedication(Medication $medication, ?CarbonInterface $now = null): int
    {
        $now = CarbonImmutable::instance($now ?? now());

        return $this->activeSchedules()
            ->where('medication_id', $medication->id)
            ->get()
            ->sum(fn (MedicationSchedule $schedule) => $this->forSchedule($schedule, $now));
    }

    /**
     * Create the doses of a single schedule between now and HOURS_AHEAD.
     */
    private function forSchedule(MedicationSchedule $schedule, CarbonImmutable $now): int
    {
        $medication = $schedule->medication;
        $user = $medication->user;
        $until = $now->addHours(self::HOURS_AHEAD);
        $created = 0;

        $day = $now->setTimezone($user->timezone)->startOfDay();
        $lastDay = $until->setTimezone($user->timezone)->startOfDay();

        for (; $day->lessThanOrEqualTo($lastDay); $day = $day->addDay()) {
            if (! $this->takesOn($schedule, $day)) {
                continue;
            }

            $scheduledFor = CarbonImmutable::parse($day->toDateString().' '.$schedule->time, $user->timezone)->utc();

            if ($scheduledFor->lessThan($now) || $scheduledFor->greaterThan($until)) {
                continue;
            }

            $reminder = $user->reminders()->firstOrCreate(['scheduled_for' => $scheduledFor]);

            $dose = $reminder->doses()->firstOrCreate(
                ['medication_schedule_id' => $schedule->id, 'scheduled_for' => $scheduledFor],
                ['medication_id' => $medication->id, 'amount' => $schedule->dose_amount ?? $medication->dose_amount],
            );

            if ($dose->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Determine if the medication is taken on this local day, according to the treatment period and week days.
     */
    private function takesOn(MedicationSchedule $schedule, CarbonImmutable $day): bool
    {
        $medication = $schedule->medication;
        $date = $day->toDateString();

        if ($date < $medication->starts_on->toDateString()) {
            return false;
        }

        if ($medication->ends_on !== null && $date > $medication->ends_on->toDateString()) {
            return false;
        }

        return $schedule->days_of_week === null || in_array($day->dayOfWeekIso, $schedule->days_of_week, true);
    }

    /**
     * Active schedules of active (not archived, not deleted) medications.
     *
     * @return Builder<MedicationSchedule>
     */
    private function activeSchedules(): Builder
    {
        return MedicationSchedule::query()
            ->where('is_active', true)
            ->whereHas('medication', fn (Builder $query) => $query->where('is_active', true))
            ->with('medication.user');
    }
}

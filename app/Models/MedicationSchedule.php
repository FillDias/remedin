<?php

namespace App\Models;

use Database\Factories\MedicationScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $medication_id
 * @property string $time
 * @property array<int, int>|null $days_of_week
 * @property string|null $dose_amount
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['time', 'days_of_week', 'dose_amount', 'is_active'])]
class MedicationSchedule extends Model
{
    /** @use HasFactory<MedicationScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'dose_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the time as "HH:MM".
     */
    public function timeLabel(): string
    {
        return substr($this->time, 0, 5);
    }

    /**
     * Get the days of the week as shown to the user, e.g. "Todos os dias" or "Seg, Qua, Sex".
     */
    public function daysLabel(): string
    {
        if ($this->days_of_week === null || count($this->days_of_week) === 7) {
            return __('Every day');
        }

        $names = self::dayNames();

        return collect($this->days_of_week)
            ->sort()
            ->map(fn (int $day): string => $names[$day])
            ->implode(', ');
    }

    /**
     * Get the short day names indexed by ISO day of the week (1 = Monday).
     *
     * @return array<int, string>
     */
    public static function dayNames(): array
    {
        return [1 => __('Mon'), 2 => __('Tue'), 3 => __('Wed'), 4 => __('Thu'), 5 => __('Fri'), 6 => __('Sat'), 7 => __('Sun')];
    }

    /**
     * Get the average amount taken per day by this schedule.
     */
    public function dailyConsumption(float $defaultDoseAmount): float
    {
        $amount = $this->dose_amount !== null ? (float) $this->dose_amount : $defaultDoseAmount;
        $daysPerWeek = $this->days_of_week === null ? 7 : count($this->days_of_week);

        return $amount * $daysPerWeek / 7;
    }

    /**
     * Get the medication this schedule belongs to.
     *
     * @return BelongsTo<Medication, $this>
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}

<?php

namespace App\Models;

use App\Enums\MealRelation;
use App\Enums\MedicationForm;
use Database\Factories\MedicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $strength
 * @property MedicationForm $form
 * @property string $dose_amount
 * @property string $dose_unit
 * @property MealRelation $meal_relation
 * @property string|null $stock_quantity
 * @property int $low_stock_days
 * @property Carbon|null $low_stock_notified_at
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property string|null $notes
 * @property string|null $photo_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'strength',
    'form',
    'dose_amount',
    'dose_unit',
    'meal_relation',
    'stock_quantity',
    'low_stock_days',
    'starts_on',
    'ends_on',
    'notes',
    'is_active',
])]
class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'form' => MedicationForm::class,
            'meal_relation' => MealRelation::class,
            'dose_amount' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'low_stock_days' => 'integer',
            'low_stock_notified_at' => 'datetime',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user who owns the medication.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the times of day when the medication should be taken.
     *
     * @return HasMany<MedicationSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(MedicationSchedule::class);
    }

    /**
     * Get the dose as shown to the user, e.g. "1 comprimido" or "0,5 comprimido".
     */
    public function doseLabel(): string
    {
        return self::formatAmount($this->dose_amount).' '.$this->dose_unit;
    }

    /**
     * Estimate how many days the current stock lasts, or null when stock is not tracked.
     */
    public function daysOfStockLeft(): ?int
    {
        if ($this->stock_quantity === null || ! $this->form->tracksStock()) {
            return null;
        }

        $dailyConsumption = $this->schedules
            ->where('is_active', true)
            ->sum(fn (MedicationSchedule $schedule): float => $schedule->dailyConsumption((float) $this->dose_amount));

        if ($dailyConsumption <= 0) {
            return null;
        }

        return (int) floor((float) $this->stock_quantity / $dailyConsumption);
    }

    /**
     * Determine if the stock lasts for fewer days than the user's warning threshold.
     */
    public function isRunningLow(): bool
    {
        $daysLeft = $this->daysOfStockLeft();

        return $daysLeft !== null && $daysLeft <= $this->low_stock_days;
    }

    /**
     * Format a decimal amount the Brazilian way, without trailing zeros: "1.00" => "1", "0.50" => "0,5".
     */
    public static function formatAmount(string|float $amount): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format((float) $amount, 2, '.', ''), '0'), '.'));
    }

    /**
     * Only include medications that are not archived.
     *
     * @param  Builder<Medication>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}

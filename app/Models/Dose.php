<?php

namespace App\Models;

use App\Enums\DoseStatus;
use Database\Factories\DoseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $reminder_id
 * @property int $medication_id
 * @property int $medication_schedule_id
 * @property Carbon $scheduled_for
 * @property string $amount
 * @property DoseStatus $status
 * @property Carbon|null $taken_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['medication_id', 'medication_schedule_id', 'scheduled_for', 'amount', 'status'])]
class Dose extends Model
{
    /** @use HasFactory<DoseFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'amount' => 'decimal:2',
            'status' => DoseStatus::class,
            'taken_at' => 'datetime',
        ];
    }

    /**
     * Get the reminder this dose is part of.
     *
     * @return BelongsTo<Reminder, $this>
     */
    public function reminder(): BelongsTo
    {
        return $this->belongsTo(Reminder::class);
    }

    /**
     * Get the medication, even if it was deleted later, so the history stays readable.
     *
     * @return BelongsTo<Medication, $this>
     */
    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class)->withTrashed();
    }

    /**
     * Get the schedule that generated this dose.
     *
     * @return BelongsTo<MedicationSchedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MedicationSchedule::class, 'medication_schedule_id');
    }
}

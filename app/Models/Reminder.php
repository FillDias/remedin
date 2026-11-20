<?php

namespace App\Models;

use App\Enums\DoseStatus;
use App\Enums\ReminderStatus;
use Database\Factories\ReminderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $scheduled_for
 * @property ReminderStatus $status
 * @property Carbon|null $sent_at
 * @property Carbon|null $follow_up_sent_at
 * @property Carbon|null $answered_at
 * @property Carbon|null $snoozed_until
 * @property string|null $whatsapp_message_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['scheduled_for', 'status'])]
class Reminder extends Model
{
    /** @use HasFactory<ReminderFactory> */
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
            'status' => ReminderStatus::class,
            'sent_at' => 'datetime',
            'follow_up_sent_at' => 'datetime',
            'answered_at' => 'datetime',
            'snoozed_until' => 'datetime',
        ];
    }

    /**
     * Get the user who receives the reminder.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the reminder as answered once none of its doses is pending anymore.
     */
    public function markAnsweredIfComplete(): void
    {
        if ($this->doses()->where('status', DoseStatus::Pending)->exists()) {
            return;
        }

        $this->forceFill([
            'status' => ReminderStatus::Answered,
            'answered_at' => now(),
        ])->save();
    }

    /**
     * Get the doses grouped in this reminder.
     *
     * @return HasMany<Dose, $this>
     */
    public function doses(): HasMany
    {
        return $this->hasMany(Dose::class);
    }
}

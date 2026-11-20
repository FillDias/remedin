<?php

namespace App\Actions;

use App\Enums\DoseStatus;
use App\Enums\ReminderStatus;
use App\Models\Dose;
use Illuminate\Support\Facades\DB;

class UndoDoseAnswer
{
    /**
     * Put a taken or skipped dose back to pending, e.g. after a tap by mistake.
     */
    public function handle(Dose $dose): void
    {
        DB::transaction(function () use ($dose) {
            $dose = Dose::query()->lockForUpdate()->findOrFail($dose->id);

            if (! in_array($dose->status, [DoseStatus::Taken, DoseStatus::Skipped], true)) {
                return;
            }

            if ($dose->status === DoseStatus::Taken) {
                $dose->medication->adjustStock((float) $dose->amount);
            }

            $dose->forceFill(['status' => DoseStatus::Pending, 'taken_at' => null])->save();

            $reminder = $dose->reminder;

            // A future reminder must still be sent. A past one is not sent again.
            if ($reminder->status === ReminderStatus::Answered && $reminder->scheduled_for->isFuture()) {
                $reminder->forceFill(['status' => ReminderStatus::Pending, 'answered_at' => null])->save();
            }
        });
    }
}

<?php

namespace App\Actions;

use App\Enums\DoseStatus;
use App\Models\Dose;
use Illuminate\Support\Facades\DB;

class SkipDose
{
    /**
     * Record that the user chose not to take the dose.
     */
    public function handle(Dose $dose): void
    {
        DB::transaction(function () use ($dose) {
            $dose = Dose::query()->lockForUpdate()->findOrFail($dose->id);

            if ($dose->status === DoseStatus::Skipped) {
                return;
            }

            if ($dose->status === DoseStatus::Taken) {
                $dose->medication->adjustStock((float) $dose->amount);
            }

            $dose->forceFill(['status' => DoseStatus::Skipped, 'taken_at' => null])->save();
            $dose->reminder->markAnsweredIfComplete();
        });
    }
}

<?php

namespace App\Actions;

use App\Enums\DoseStatus;
use App\Models\Dose;
use Illuminate\Support\Facades\DB;

class MarkDoseAsTaken
{
    /**
     * Record that the dose was taken and take it out of the stock.
     *
     * The row is locked while it changes, so a double tap (or the website and WhatsApp
     * answering at the same time) never discounts the stock twice.
     */
    public function handle(Dose $dose): void
    {
        DB::transaction(function () use ($dose) {
            $dose = Dose::query()->lockForUpdate()->findOrFail($dose->id);

            if ($dose->status === DoseStatus::Taken) {
                return;
            }

            $dose->forceFill(['status' => DoseStatus::Taken, 'taken_at' => now()])->save();
            $dose->medication->adjustStock(-(float) $dose->amount);
            $dose->reminder->markAnsweredIfComplete();
        });
    }
}

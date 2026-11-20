<?php

namespace App\Actions;

use App\Enums\MedicationForm;
use App\Models\Medication;

class UpdateMedication
{
    public function __construct(private RefreshMedicationDoses $refreshMedicationDoses) {}

    /**
     * Update the medication's details.
     *
     * @param  array<string, mixed>  $data  Validated medication fields.
     */
    public function handle(Medication $medication, array $data): void
    {
        if (! MedicationForm::from($data['form'])->tracksStock()) {
            $data['stock_quantity'] = null;
        }

        $medication->fill($data);

        if ($medication->isDirty('stock_quantity') && ! $medication->isRunningLow()) {
            $medication->low_stock_notified_at = null;
        }

        $changesUpcomingDoses = $medication->isDirty(['dose_amount', 'starts_on', 'ends_on']);

        $medication->save();

        if ($changesUpcomingDoses) {
            $this->refreshMedicationDoses->handle($medication);
        }
    }
}

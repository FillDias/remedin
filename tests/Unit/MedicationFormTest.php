<?php

use App\Enums\MedicationForm;

test('stock is tracked only for forms counted in units', function (MedicationForm $form, bool $tracksStock) {
    expect($form->tracksStock())->toBe($tracksStock);
})->with([
    'tablet' => [MedicationForm::Tablet, true],
    'capsule' => [MedicationForm::Capsule, true],
    'softgel' => [MedicationForm::Softgel, true],
    'effervescent' => [MedicationForm::Effervescent, true],
    'drops' => [MedicationForm::Drops, false],
    'liquid' => [MedicationForm::Liquid, false],
    'powder' => [MedicationForm::Powder, false],
    'other' => [MedicationForm::Other, false],
]);

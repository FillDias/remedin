<?php

use App\Actions\ArchiveMedication;
use App\Actions\CreateMedication;
use App\Actions\UnarchiveMedication;
use App\Actions\UpdateMedication;
use App\Enums\MealRelation;
use App\Enums\MedicationForm;
use App\Models\Medication;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public ?Medication $medication = null;

    public string $name = '';
    public string $strength = '';
    public string $form = 'tablet';
    public string $dose_amount = '1';
    public string $dose_unit = '';
    public string $meal_relation = 'any';
    public string $stock_quantity = '';
    public string $low_stock_days = '5';
    public string $starts_on = '';
    public string $ends_on = '';
    public string $notes = '';

    /**
     * Load the medication being edited, or prepare an empty form.
     */
    public function mount(?Medication $medication = null): void
    {
        if ($medication?->exists) {
            $this->authorize('update', $medication);

            $this->medication = $medication;
            $this->name = $medication->name;
            $this->strength = (string) $medication->strength;
            $this->form = $medication->form->value;
            $this->dose_amount = Medication::formatAmount($medication->dose_amount);
            $this->dose_unit = $medication->dose_unit;
            $this->meal_relation = $medication->meal_relation->value;
            $this->stock_quantity = $medication->stock_quantity === null ? '' : Medication::formatAmount($medication->stock_quantity);
            $this->low_stock_days = (string) $medication->low_stock_days;
            $this->starts_on = $medication->starts_on->toDateString();
            $this->ends_on = (string) $medication->ends_on?->toDateString();
            $this->notes = (string) $medication->notes;

            return;
        }

        $this->authorize('create', Medication::class);

        $this->dose_unit = MedicationForm::Tablet->defaultDoseUnit();
        $this->starts_on = today(Auth::user()->timezone)->toDateString();
    }

    /**
     * Suggest a dose unit for the chosen form, unless the user typed a custom one.
     */
    public function updatedForm(string $value): void
    {
        $form = MedicationForm::tryFrom($value);

        if ($form === null) {
            return;
        }

        $defaultUnits = array_map(fn (MedicationForm $case) => $case->defaultDoseUnit(), MedicationForm::cases());

        if ($this->dose_unit === '' || in_array($this->dose_unit, $defaultUnits, true)) {
            $this->dose_unit = $form->defaultDoseUnit();
        }
    }

    /**
     * Determine if the stock fields apply to the chosen form.
     */
    #[Computed]
    public function tracksStock(): bool
    {
        return MedicationForm::tryFrom($this->form)?->tracksStock() ?? false;
    }

    /**
     * Validate and save the medication.
     */
    public function save(CreateMedication $createMedication, UpdateMedication $updateMedication): void
    {
        $this->dose_amount = str_replace(',', '.', $this->dose_amount);
        $this->stock_quantity = str_replace(',', '.', $this->stock_quantity);

        $validated = $this->validate();

        $data = [
            ...$validated,
            'strength' => filled($validated['strength']) ? $validated['strength'] : null,
            'stock_quantity' => filled($validated['stock_quantity']) ? $validated['stock_quantity'] : null,
            'ends_on' => filled($validated['ends_on']) ? $validated['ends_on'] : null,
            'notes' => filled($validated['notes']) ? $validated['notes'] : null,
        ];

        if ($this->medication !== null) {
            $this->authorize('update', $this->medication);
            $updateMedication->handle($this->medication, $data);

            Flux::toast(variant: 'success', text: __('Medication saved.'));
            $this->redirectRoute('medications.index', navigate: true);

            return;
        }

        $medication = $createMedication->handle(Auth::user(), $data);

        Flux::toast(variant: 'success', text: __('Medication saved. Now add the reminder times.'));
        $this->redirectRoute('medications.edit', ['medication' => $medication], navigate: true);
    }

    /**
     * Archive the medication: no more reminders, history is kept.
     */
    public function archive(ArchiveMedication $archiveMedication): void
    {
        $this->authorize('update', $this->medication);

        $archiveMedication->handle($this->medication);

        Flux::toast(text: __(':name was archived. You will no longer receive reminders for it.', ['name' => $this->medication->name]));
        $this->redirectRoute('medications.index', navigate: true);
    }

    /**
     * Bring an archived medication back to the active list.
     */
    public function unarchive(UnarchiveMedication $unarchiveMedication): void
    {
        $this->authorize('update', $this->medication);

        $unarchiveMedication->handle($this->medication);

        Flux::toast(variant: 'success', text: __(':name is active again.', ['name' => $this->medication->name]));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'strength' => ['nullable', 'string', 'max:50'],
            'form' => ['required', Rule::enum(MedicationForm::class)],
            'dose_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999.99'],
            'dose_unit' => ['required', 'string', 'max:30'],
            'meal_relation' => ['required', Rule::enum(MealRelation::class)],
            'stock_quantity' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
            'low_stock_days' => ['required', 'integer', 'min:1', 'max:60'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'name' => __('medication name'),
            'strength' => __('strength'),
            'form' => __('form'),
            'dose_amount' => __('dose'),
            'dose_unit' => __('dose unit'),
            'meal_relation' => __('meals'),
            'stock_quantity' => __('stock'),
            'low_stock_days' => __('warning days'),
            'starts_on' => __('start date'),
            'ends_on' => __('end date'),
            'notes' => __('notes'),
        ];
    }

    public function render()
    {
        return $this->view()->title($this->medication ? __('Edit medication') : __('New medication'));
    }
}; ?>

<section class="mx-auto w-full max-w-2xl">
    <div class="mb-6">
        <flux:link :href="route('medications.index')" wire:navigate variant="subtle" class="text-sm">
            ← {{ __('My medications') }}
        </flux:link>
        <flux:heading size="xl" level="1" class="mt-2">
            {{ $medication ? __('Edit medication') : __('New medication') }}
        </flux:heading>
    </div>

    @if ($medication && ! $medication->is_active)
        <flux:callout variant="secondary" icon="archive-box" class="mb-8" :heading="__('This medication is archived')">
            <flux:callout.text>{{ __('You are not receiving reminders for it. Your history is kept.') }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button wire:click="unarchive" size="sm" data-test="unarchive-medication-button">{{ __('Reactivate') }}</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    <form wire:submit="save" class="flex flex-col gap-8">
        <div class="flex flex-col gap-6">
            <flux:input wire:model="name" :label="__('Name')" required :placeholder="__('E.g. Lavitan A-Z')" />

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:input wire:model="strength" :label="__('Strength')" :placeholder="__('E.g. 500 mg')" />

                <flux:select wire:model.live="form" :label="__('Form')">
                    @foreach (MedicationForm::cases() as $formOption)
                        <flux:select.option :value="$formOption->value">{{ $formOption->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:separator variant="subtle" />

        <div class="flex flex-col gap-6">
            <div>
                <flux:heading>{{ __('Dose') }}</flux:heading>
                <flux:text class="mt-1">{{ __('Enter the dose prescribed by your doctor or stated on the leaflet.') }}</flux:text>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="dose_amount" :label="__('Amount')" inputmode="decimal" required />
                <flux:input wire:model="dose_unit" :label="__('Unit')" required />
            </div>

            <flux:select wire:model="meal_relation" :label="__('Meals')">
                @foreach (MealRelation::cases() as $mealOption)
                    <flux:select.option :value="$mealOption->value">{{ $mealOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($this->tracksStock)
            <flux:separator variant="subtle" />

            <div class="flex flex-col gap-6">
                <div>
                    <flux:heading>{{ __('Stock') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Optional. Remedin warns you before it runs out.') }}</flux:text>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input wire:model="stock_quantity" :label="__('Units left')" inputmode="decimal" />
                    <flux:input wire:model="low_stock_days" :label="__('Warn me with (days)')" type="number" inputmode="numeric" min="1" max="60" required />
                </div>
            </div>
        @endif

        <flux:separator variant="subtle" />

        <div class="flex flex-col gap-6">
            <flux:heading>{{ __('Period') }}</flux:heading>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:input wire:model="starts_on" :label="__('Start date')" type="date" required />
                <flux:input
                    wire:model="ends_on"
                    :label="__('End date')"
                    type="date"
                    description:trailing="{{ __('Leave blank for continuous use.') }}"
                />
            </div>

            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <flux:button :href="route('medications.index')" wire:navigate variant="ghost">{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" data-test="save-medication-button">{{ __('Save') }}</flux:button>
        </div>
    </form>

    @if ($medication)
        <flux:separator class="my-10" />

        <livewire:medications.schedules :medication="$medication" />

        @if ($medication->is_active)
            <flux:separator class="my-10" />

            <div class="flex flex-col items-start gap-3">
                <div>
                    <flux:heading>{{ __('Stopped taking it?') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Archive it to stop the reminders. Your history is kept and you can reactivate it later.') }}</flux:text>
                </div>
                <flux:button
                    wire:click="archive"
                    wire:confirm="{{ __('Archive :name? You will no longer receive reminders for it.', ['name' => $medication->name]) }}"
                    icon="archive-box"
                    data-test="archive-medication-button"
                >
                    {{ __('Archive medication') }}
                </flux:button>
            </div>
        @endif
    @endif
</section>

<?php

use App\Actions\AddMedicationSchedule;
use App\Actions\RemoveMedicationSchedule;
use App\Models\Medication;
use App\Models\MedicationSchedule;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public Medication $medication;

    public string $time = '08:00';
    public string $frequency = 'every_day';

    /** @var array<int, string> */
    public array $days = [];

    public string $dose_amount = '';

    /**
     * Make sure only the owner can manage the schedules.
     */
    public function mount(Medication $medication): void
    {
        $this->authorize('update', $medication);

        $this->medication = $medication;
    }

    /**
     * Get the active reminder times, earliest first.
     *
     * @return Collection<int, MedicationSchedule>
     */
    #[Computed]
    public function schedules(): Collection
    {
        return $this->medication->schedules()
            ->where('is_active', true)
            ->orderBy('time')
            ->get();
    }

    /**
     * Validate and add a new reminder time.
     */
    public function add(AddMedicationSchedule $addMedicationSchedule): void
    {
        $this->authorize('update', $this->medication);

        $this->dose_amount = str_replace(',', '.', $this->dose_amount);

        $validated = $this->validate([
            'time' => ['required', 'date_format:H:i'],
            'frequency' => ['required', 'in:every_day,specific_days'],
            'days' => ['exclude_unless:frequency,specific_days', 'required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'dose_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999.99'],
        ], attributes: [
            'time' => __('time'),
            'days' => __('days of the week'),
            'dose_amount' => __('dose'),
        ]);

        $addMedicationSchedule->handle($this->medication, [
            'time' => $validated['time'],
            'days_of_week' => $this->frequency === 'specific_days' ? array_map('intval', $validated['days']) : null,
            'dose_amount' => filled($validated['dose_amount']) ? $validated['dose_amount'] : null,
        ]);

        $this->reset('frequency', 'days', 'dose_amount');
        unset($this->schedules);
    }

    /**
     * Stop reminding at the given time.
     */
    public function remove(int $scheduleId, RemoveMedicationSchedule $removeMedicationSchedule): void
    {
        $this->authorize('update', $this->medication);

        $schedule = $this->medication->schedules()->findOrFail($scheduleId);

        $removeMedicationSchedule->handle($schedule);

        unset($this->schedules);
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading>{{ __('Reminder times') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Remedin sends a WhatsApp message at each of these times.') }}</flux:text>
    </div>

    @if ($this->schedules->isEmpty())
        <flux:callout variant="secondary" icon="clock" :heading="__('No reminder times yet.')">
            <flux:callout.text>{{ __('Add at least one time to start receiving reminders.') }}</flux:callout.text>
        </flux:callout>
    @else
        <ul class="flex flex-col divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700" data-test="schedule-list">
            @foreach ($this->schedules as $schedule)
                <li wire:key="schedule-{{ $schedule->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <span class="text-lg font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $schedule->timeLabel() }}</span>
                        <flux:text class="inline">
                            · {{ $schedule->daysLabel() }}
                            · {{ Medication::formatAmount($schedule->dose_amount ?? $medication->dose_amount) }} {{ $medication->dose_unit }}
                        </flux:text>
                    </div>

                    <flux:button
                        wire:click="remove({{ $schedule->id }})"
                        wire:confirm="{{ __('Stop reminding at :time?', ['time' => $schedule->timeLabel()]) }}"
                        variant="ghost"
                        size="sm"
                        icon="trash"
                        :aria-label="__('Remove the :time reminder', ['time' => $schedule->timeLabel()])"
                    />
                </li>
            @endforeach
        </ul>
    @endif

    <form wire:submit="add" class="flex flex-col gap-5 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60">
        <flux:heading size="sm">{{ __('Add a time') }}</flux:heading>

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="time" :label="__('Time')" type="time" required />
            <flux:input
                wire:model="dose_amount"
                :label="__('Different dose')"
                inputmode="decimal"
                :placeholder="Medication::formatAmount($medication->dose_amount)"
            />
        </div>

        <flux:radio.group wire:model.live="frequency" :label="__('Days')">
            <flux:radio value="every_day" :label="__('Every day')" />
            <flux:radio value="specific_days" :label="__('Specific days')" />
        </flux:radio.group>

        @if ($frequency === 'specific_days')
            <flux:checkbox.group wire:model="days" :label="__('Which days?')" class="flex flex-wrap gap-x-5 gap-y-3">
                @foreach (MedicationSchedule::dayNames() as $dayNumber => $dayName)
                    <flux:checkbox :value="$dayNumber" :label="$dayName" />
                @endforeach
            </flux:checkbox.group>
        @endif

        <div>
            <flux:button type="submit" variant="primary" icon="plus" data-test="add-schedule-button">{{ __('Add time') }}</flux:button>
        </div>
    </form>
</div>

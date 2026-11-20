<?php

use App\Actions\MarkDoseAsTaken;
use App\Actions\SkipDose;
use App\Actions\UndoDoseAnswer;
use App\Enums\DoseStatus;
use App\Enums\MealRelation;
use App\Models\Dose;
use App\Models\Medication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public function render()
    {
        return $this->view()->title(__('Today'));
    }

    /**
     * Get today's doses, where "today" is the user's local day.
     *
     * @return Collection<int, Dose>
     */
    #[Computed]
    public function doses(): Collection
    {
        $timezone = Auth::user()->timezone;

        return Auth::user()
            ->doses()
            ->whereBetween('doses.scheduled_for', [
                now($timezone)->startOfDay()->utc(),
                now($timezone)->endOfDay()->utc(),
            ])
            ->with('medication')
            ->orderBy('doses.scheduled_for')
            ->get();
    }

    /**
     * Get today's doses grouped by local time, e.g. ["08:00" => [...], "20:00" => [...]].
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, Dose>>
     */
    #[Computed]
    public function timeline(): \Illuminate\Support\Collection
    {
        $timezone = Auth::user()->timezone;

        return $this->doses->groupBy(fn (Dose $dose) => $dose->scheduled_for->setTimezone($timezone)->format('H:i'));
    }

    #[Computed]
    public function takenCount(): int
    {
        return $this->doses->where('status', DoseStatus::Taken)->count();
    }

    #[Computed]
    public function hasMedications(): bool
    {
        return Auth::user()->medications()->exists();
    }

    public function take(int $doseId, MarkDoseAsTaken $markDoseAsTaken): void
    {
        $markDoseAsTaken->handle($this->findDose($doseId));

        $this->refreshTimeline();
    }

    public function skip(int $doseId, SkipDose $skipDose): void
    {
        $skipDose->handle($this->findDose($doseId));

        $this->refreshTimeline();
    }

    public function undo(int $doseId, UndoDoseAnswer $undoDoseAnswer): void
    {
        $undoDoseAnswer->handle($this->findDose($doseId));

        $this->refreshTimeline();
    }

    /**
     * Find one of the user's doses. Doses of other users are "not found".
     */
    private function findDose(int $doseId): Dose
    {
        $dose = Auth::user()->doses()->findOrFail($doseId);

        $this->authorize('update', $dose);

        return $dose;
    }

    /**
     * Forget the cached computed values so the timeline shows the new statuses.
     */
    private function refreshTimeline(): void
    {
        unset($this->doses, $this->timeline, $this->takenCount);
    }
}; ?>

<section class="mx-auto w-full max-w-2xl">
    <div class="mb-6">
        <flux:heading size="xl" level="1">{{ __('Today') }}</flux:heading>
        <flux:text class="mt-1 first-letter:uppercase">
            {{ now(auth()->user()->timezone)->translatedFormat('l, j \d\e F') }}
        </flux:text>
    </div>

    @if ($this->doses->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-600">
            @if ($this->hasMedications)
                <flux:heading size="lg">{{ __('No doses today') }}</flux:heading>
                <flux:text class="mx-auto mt-2 max-w-sm">{{ __('None of your medications has a reminder time today.') }}</flux:text>
            @else
                <flux:heading size="lg">{{ __('No medications yet') }}</flux:heading>
                <flux:text class="mx-auto mt-2 max-w-sm">
                    {{ __('Add your first medication to start receiving reminders on WhatsApp.') }}
                </flux:text>
                <flux:button :href="route('medications.create')" wire:navigate variant="primary" icon="plus" class="mt-6">
                    {{ __('Add medication') }}
                </flux:button>
            @endif
        </div>
    @else
        <div class="mb-8 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="today-summary">
            @if ($this->takenCount === $this->doses->count())
                <flux:heading size="lg">{{ __('You took everything today 🎉') }}</flux:heading>
            @else
                <flux:heading size="lg">
                    {{ trans_choice(':taken of :total dose taken today|:taken of :total doses taken today', $this->doses->count(), [
                        'taken' => $this->takenCount,
                        'total' => $this->doses->count(),
                    ]) }}
                </flux:heading>
            @endif

            <div
                class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"
                role="progressbar"
                aria-valuemin="0"
                aria-valuemax="{{ $this->doses->count() }}"
                aria-valuenow="{{ $this->takenCount }}"
                aria-label="{{ __('Doses taken today') }}"
            >
                <div class="h-full rounded-full bg-accent" style="width: {{ round($this->takenCount / $this->doses->count() * 100) }}%"></div>
            </div>
        </div>

        <ol class="flex flex-col gap-6" data-test="today-timeline">
            @foreach ($this->timeline as $time => $doses)
                <li wire:key="time-{{ $time }}">
                    <h2 class="mb-2 text-lg font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $time }}</h2>

                    <ul class="flex flex-col divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-900">
                        @foreach ($doses as $dose)
                            <li wire:key="dose-{{ $dose->id }}" class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $dose->medication->name }}</p>

                                        @if ($dose->status !== DoseStatus::Pending)
                                            <flux:badge
                                                size="sm"
                                                :color="match ($dose->status) {
                                                    DoseStatus::Taken => 'teal',
                                                    DoseStatus::Missed => 'red',
                                                    default => 'zinc',
                                                }"
                                                class="shrink-0"
                                            >
                                                {{ $dose->status->label() }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                    <flux:text>
                                        {{ Medication::formatAmount($dose->amount) }} {{ $dose->medication->dose_unit }}
                                        @if ($dose->medication->meal_relation !== MealRelation::Any)
                                            · {{ $dose->medication->meal_relation->label() }}
                                        @endif
                                    </flux:text>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if (in_array($dose->status, [DoseStatus::Pending, DoseStatus::Missed], true))
                                        <flux:button
                                            wire:click="take({{ $dose->id }})"
                                            variant="primary"
                                            icon="check"
                                            class="flex-1 sm:flex-none"
                                            :aria-label="__('Mark :name as taken', ['name' => $dose->medication->name])"
                                        >
                                            {{ __('Took it') }}
                                        </flux:button>
                                        <flux:button
                                            wire:click="skip({{ $dose->id }})"
                                            variant="ghost"
                                            :aria-label="__('Skip :name', ['name' => $dose->medication->name])"
                                        >
                                            {{ __('Skip') }}
                                        </flux:button>
                                    @else
                                        <flux:button
                                            wire:click="undo({{ $dose->id }})"
                                            variant="ghost"
                                            size="sm"
                                            icon="arrow-uturn-left"
                                            :aria-label="__('Undo the answer for :name', ['name' => $dose->medication->name])"
                                        >
                                            {{ __('Undo') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ol>
    @endif
</section>

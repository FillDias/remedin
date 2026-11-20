<?php

use App\Models\Medication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('My medications')] class extends Component {
    #[Url(as: 'arquivados')]
    public bool $showArchived = false;

    /**
     * Get the user's active (or archived) medications with their active schedules.
     *
     * @return Collection<int, Medication>
     */
    #[Computed]
    public function medications(): Collection
    {
        return Auth::user()
            ->medications()
            ->where('is_active', ! $this->showArchived)
            ->with(['schedules' => fn ($query) => $query->where('is_active', true)->orderBy('time')])
            ->orderBy('name')
            ->get();
    }

    /**
     * Count the archived medications, to decide whether to show the link to them.
     */
    #[Computed]
    public function archivedCount(): int
    {
        return Auth::user()->medications()->where('is_active', false)->count();
    }
}; ?>

<section class="mx-auto w-full max-w-2xl">
    @if ($showArchived)
        <div class="mb-6">
            <flux:link wire:click="$set('showArchived', false)" variant="subtle" class="cursor-pointer text-sm">
                ← {{ __('My medications') }}
            </flux:link>
            <flux:heading size="xl" level="1" class="mt-2">{{ __('Archived') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Medications you no longer take. Open one to reactivate it.') }}</flux:text>
        </div>
    @else
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('My medications') }}</flux:heading>
                <flux:text class="mt-1">{{ __('What you take and when Remedin will remind you.') }}</flux:text>
            </div>

            @if ($this->medications->isNotEmpty())
                <flux:button :href="route('medications.create')" wire:navigate variant="primary" icon="plus" class="shrink-0">
                    {{ __('Add') }}
                </flux:button>
            @endif
        </div>
    @endif

    @if ($showArchived && $this->medications->isEmpty())
        <flux:text>{{ __('No archived medications.') }}</flux:text>
    @elseif ($this->medications->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-600">
            <flux:heading size="lg">{{ __('No medications yet') }}</flux:heading>
            <flux:text class="mx-auto mt-2 max-w-sm">
                {{ __('Add your first medication to start receiving reminders on WhatsApp.') }}
            </flux:text>
            <flux:button :href="route('medications.create')" wire:navigate variant="primary" icon="plus" class="mt-6">
                {{ __('Add medication') }}
            </flux:button>
        </div>
    @else
        <ul class="flex flex-col gap-3" data-test="medication-list">
            @foreach ($this->medications as $medication)
                <li wire:key="medication-{{ $medication->id }}">
                <a
                    href="{{ route('medications.edit', $medication) }}"
                    wire:navigate
                    aria-label="{{ __('Edit :name', ['name' => $medication->name]) }}"
                    class="block rounded-xl border border-zinc-200 bg-white p-4 transition-colors hover:border-zinc-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <flux:heading size="lg" class="truncate">{{ $medication->name }}</flux:heading>
                            <flux:text class="mt-0.5">
                                {{ $medication->doseLabel() }}
                                @if ($medication->strength)
                                    · {{ $medication->strength }}
                                @endif
                                · {{ $medication->meal_relation->label() }}
                            </flux:text>
                        </div>

                        @if ($medication->isRunningLow())
                            <flux:badge color="amber" size="sm" class="shrink-0">{{ __('Running low') }}</flux:badge>
                        @endif
                    </div>

                    @if ($medication->schedules->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($medication->schedules as $schedule)
                                <li wire:key="schedule-{{ $schedule->id }}" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-sm dark:bg-zinc-800">
                                    <span class="font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $schedule->timeLabel() }}</span>
                                    <span class="text-zinc-500 dark:text-zinc-400">· {{ $schedule->daysLabel() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <flux:text class="mt-3">{{ __('No reminder times yet.') }}</flux:text>
                    @endif

                    @if ($medication->daysOfStockLeft() !== null)
                        <flux:text size="sm" class="mt-3">
                            {{ trans_choice('Stock: :stock left, lasts about :days day|Stock: :stock left, lasts about :days days', $medication->daysOfStockLeft(), [
                                'stock' => \App\Models\Medication::formatAmount($medication->stock_quantity),
                                'days' => $medication->daysOfStockLeft(),
                            ]) }}
                        </flux:text>
                    @endif
                </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if (! $showArchived && $this->archivedCount > 0)
        <div class="mt-8 text-center">
            <flux:link wire:click="$set('showArchived', true)" variant="subtle" class="cursor-pointer text-sm" data-test="show-archived-link">
                {{ __('View archived (:count)', ['count' => $this->archivedCount]) }}
            </flux:link>
        </div>
    @endif
</section>

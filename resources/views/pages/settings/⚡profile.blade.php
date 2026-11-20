<?php

use App\Actions\NormalizePhoneNumber;
use App\Actions\UpdateUserProfile;
use App\Concerns\ProfileValidationRules;
use App\Enums\BrazilianTimezone;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $timezone = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = Auth::user()->nationalPhone();
        $this->timezone = Auth::user()->timezone;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(
        NormalizePhoneNumber $normalizePhoneNumber,
        UpdateUserProfile $updateUserProfile,
    ): void {
        $user = Auth::user();

        if (config('fortify.lowercase_usernames')) {
            $this->email = Str::lower($this->email);
        }

        $validated = Validator::make([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $normalizePhoneNumber->handle($this->phone),
            'timezone' => $this->timezone,
        ], [
            ...$this->profileRules($user->id),
            'phone' => $this->phoneRules($user->id),
            'timezone' => $this->timezoneRules(),
        ], $this->phoneMessages())->validate();

        $updateUserProfile->handle($user, $validated);

        $this->phone = $user->nationalPhone();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your contact details and time zone')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <flux:input
                wire:model="phone"
                :label="__('WhatsApp number')"
                description:trailing="{{ __('Changing your number requires verifying it again.') }}"
                type="tel"
                inputmode="tel"
                mask="(99) 99999-9999"
                required
                autocomplete="tel-national"
                placeholder="(11) 98765-4321"
            />

            <flux:select
                wire:model="timezone"
                :label="__('Time zone')"
                description:trailing="{{ __('Your reminders follow this time zone.') }}"
            >
                @foreach (BrazilianTimezone::cases() as $timezoneOption)
                    <flux:select.option :value="$timezoneOption->value">{{ $timezoneOption->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>

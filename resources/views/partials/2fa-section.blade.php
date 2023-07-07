// resources/views/partials/2fa-section.blade.php
<hr />

<x-filament-jetstream::grid-section class="mt-8">
    <x-slot name="title">
        {{ __('filament-2fa::two-factor.title') }}
    </x-slot>

    <x-slot name="description">
        {{ __('filament-2fa::two-factor.description') }}
    </x-slot>

    <div class="space-y-3">
        <x-filament::card>
            <livewire:filament-two-factor-form>
        </x-filament::card>
    </div>
</x-filament-jetstream::grid-section>
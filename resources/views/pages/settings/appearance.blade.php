<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.marketplace-settings')] #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    <flux:heading level="2" class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Apparence')" :subheading="__('Choisissez le thème de votre espace admin')">
        <div class="settings-appearance-card">
            <div class="settings-appearance-intro"><span class="settings-appearance-icon"><x-ui-icon name="sun" /></span><div><strong>Thème de l’interface</strong><p>Votre préférence est enregistrée pour vos prochaines visites.</p></div></div>
            <flux:radio.group x-data x-effect="(() => { const mode = $flux.appearance; const dark = mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches); document.body.dataset.theme = dark ? 'dark' : 'light'; localStorage.setItem('market-theme', dark ? 'dark' : 'light'); })()" variant="segmented" x-model="$flux.appearance">
            <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
            <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </div>
    </x-pages::settings.layout>
</section>

<div class="settings-page-layout">
    <aside class="settings-page-nav">
        <div class="settings-page-nav-heading"><span class="settings-page-nav-icon"><x-ui-icon name="settings" /></span><div><strong>Paramètres du compte</strong><small>Préférences et sécurité</small></div></div>
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            @if(auth()->user()->is_admin)
                <flux:navlist.item :href="route('admin.index')">{{ __('Retour à l’administration') }}</flux:navlist.item>
            @endif
        </flux:navlist>
    </aside>
    <section class="settings-page-content">
        <div class="settings-page-heading"><span class="admin-kicker">Paramètres du compte</span><h2>{{ $heading ?? '' }}</h2><p>{{ $subheading ?? '' }}</p></div>
        <div class="settings-page-form">{{ $slot }}</div>
    </section>
</div>

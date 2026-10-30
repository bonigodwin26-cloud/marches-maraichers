<?php

namespace App\Providers;

/* @chisel-registration */
use App\Actions\Fortify\CreateNewUser;
/* @end-chisel-registration */
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        /* @chisel-registration */
        Fortify::createUsersUsing(CreateNewUser::class);
        /* @end-chisel-registration */
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => $this->authPage($request, 'auth-login', 'Connexion'));
        /* @chisel-email-verification */
        Fortify::verifyEmailView(fn (Request $request) => $this->authPage($request, 'auth-verify', 'Vérification de votre adresse'));
        /* @end-chisel-email-verification */
        /* @chisel-2fa */
        Fortify::twoFactorChallengeView(fn (Request $request) => $this->authPage($request, 'auth-two-factor', 'Vérification en deux étapes'));
        /* @end-chisel-2fa */
        /* @chisel-password-confirmation */
        Fortify::confirmPasswordView(fn (Request $request) => $this->authPage($request, 'auth-confirm', 'Confirmer le mot de passe'));
        /* @end-chisel-password-confirmation */
        /* @chisel-registration */
        Fortify::registerView(fn (Request $request) => $this->authPage($request, 'auth-register', 'Créer un compte'));
        /* @end-chisel-registration */
        Fortify::resetPasswordView(fn (Request $request) => $this->authPage($request, 'auth-reset', 'Nouveau mot de passe'));
        Fortify::requestPasswordResetLinkView(fn (Request $request) => $this->authPage($request, 'auth-forgot', 'Mot de passe oublié'));
    }

    private function authPage(Request $request, string $page, string $title)
    {
        return view('marketplace.react', [
            'page' => $page,
            'title' => $title,
            'props' => [
                'statusMessage' => session('status'),
                'errors' => session('errors')?->getBag('default')->toArray() ?? [],
                'email' => old('email', $request->query('email', '')),
                'name' => old('name', ''),
                'token' => $request->route('token'),
            ],
        ]);
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        /* @chisel-passkeys */
        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
        /* @end-chisel-passkeys */
    }
}

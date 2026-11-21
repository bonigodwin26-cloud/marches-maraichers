<?php

namespace App\Http\Controllers;

use App\Services\EmailVerificationCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailCodeController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $user = $request->user();
        $codes = app(EmailVerificationCode::class);

        if (! $codes->verify($user, $data['code'])) {
            return back()->withErrors([
                'code' => 'Ce code est incorrect, expiré ou le nombre de tentatives est dépassé. Demandez un nouveau code.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $codes->clear($user);

        return redirect()->route('dashboard', ['verified' => 1]);
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard', ['verified' => 1]);
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-code-sent');
    }
}

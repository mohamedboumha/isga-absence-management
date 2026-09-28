<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider {
    public function register() : void {
        //
    }



    public function boot() : void {
        $this->configureActions();
        $this->configureAuthentication();
        $this->configureViews();
        $this->configureRateLimiting();
    }



    private function configureActions() : void {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }



    private function configureAuthentication() : void {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)
                        ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return null; // wrong email or password: Fortify shows its normal error
            }

            if (!$user->actif) {
                throw ValidationException::withMessages([
                                                            'email' => 'Ce compte est désactivé. Contactez le super-administrateur.',
                                                        ]);
            }

            return $user;
        });
    }



    private function configureViews() : void {
        Fortify::loginView(fn(Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'status'           => $request->session()
                                          ->get('status'),
        ]));

        Fortify::resetPasswordView(fn(Request $request) => Inertia::render('auth/ResetPassword', [
            'email'         => $request->email,
            'token'         => $request->route('token'),
            'passwordRules' => Password::defaults()
                                       ->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn(Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()
                                ->get('status'),
        ]));

        Fortify::confirmPasswordView(fn() => Inertia::render('auth/ConfirmPassword'));
    }



    private function configureRateLimiting() : void {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)
                        ->by($throttleKey);
        });
    }
}

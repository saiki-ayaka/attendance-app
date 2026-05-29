<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use App\Http\Responses\LogoutResponse;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::verifyEmailView(function () { return view('auth.verify-email'); });

        Fortify::registerView(function () {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            if (request()->is('admin/*')) {
                return view('admin.auth.login');
            }
            return view('auth.login');
        });

        // 認証ロジックの統合
        Fortify::authenticateUsing(function ($request) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                if ($request->is('admin/*')) {
                    return $user->role === 2 ? $user : null;
                }
                return $user;
            }
            return null;
        });

        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    if (auth()->user()->role == 2) {
                        return redirect()->route('admin.attendance.list');
                    }
                    return redirect()->route('attendance.list');
                }
            };
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());
            return Limit::perMinute(5)->by($throttleKey);
        });

        // ★2. ここに追加（bootメソッド内の一番下でOK）
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LogoutResponse::class,
            LogoutResponse::class
        );
    }
}
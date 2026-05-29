<?php

namespace App\Providers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Lang::addLines([
            'auth.failed' => 'ログイン情報が登録されていません',
        ], 'ja');

        App::setLocale('ja');

        Paginator::defaultView('pagination::bootstrap-4');
        //Paginator::useBootstrapFour();
    }
}

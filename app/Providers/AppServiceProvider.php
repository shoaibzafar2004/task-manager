<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        // This database holds real tasks, so block commands that wipe it (migrate:fresh,
        // migrate:refresh, migrate:reset, db:wipe). The test suite still needs them.
        DB::prohibitDestructiveCommands(! $this->app->runningUnitTests());
    }
}

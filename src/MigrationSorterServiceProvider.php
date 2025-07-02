<?php declare(strict_types=1);

namespace AbdullahKaramDev\MigrationSorter;

use Illuminate\Support\ServiceProvider;
use Laravel\MigrationSorter\Commands\SortingMigrate;

class MigrationSorterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SortingMigrate::class,
            ]);
        }
    }
}
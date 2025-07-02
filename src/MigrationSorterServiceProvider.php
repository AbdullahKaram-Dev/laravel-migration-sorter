<?php declare(strict_types=1);

namespace AbdullahKaramDev\MigrationSorter;

use AbdullahKaramDev\MigrationSorter\Commands\SortingMigration;
use Illuminate\Support\ServiceProvider;


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
                SortingMigration::class,
            ]);
        }
    }
}
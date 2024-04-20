<?php

namespace App\Providers;

use App\Http\Middleware\Locale;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Passport\Passport;
use Nexus\Nexus;
use Filament\Facades\Filament;
use NexusPlugin\Menu\Filament\MenuItemResource;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreMigrations();
        do_action('nexus_register');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//        JsonResource::withoutWrapping();
        DB::connection(config('database.default'))->enableQueryLog();

        Filament::serving(function () {
            Filament::registerNavigationGroups([
                'User',
                'Torrent',
                'Role & Permission',
                'Other',
                'Section',
                'Oauth',
                'System',
            ]);
        });

        Filament::registerStyles([
            asset('styles/sprites.css'),
            asset('styles/admin.css'),
        ]);

        do_action('nexus_boot');
    }
}

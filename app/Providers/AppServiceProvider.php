<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TherapyProgram;
use App\Policies\TherapyProgramPolicy;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Throwable;



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
    public function boot()
    {
        Gate::policy(TherapyProgram::class, TherapyProgramPolicy::class);

        // مشاركة إعدادات العيادة مع جميع الـ Views
        View::composer('*', function ($view) {
            static $settingsLoaded = false;
            static $settings = null;

            $viewData = $view->getData();
            if (array_key_exists('settings', $viewData) && $viewData['settings']) {
                return;
            }

            if (! $settingsLoaded || $settings === null) {
                $settingsLoaded = true;

                try {
                    $settings = Schema::hasTable('settings') ? Setting::first() : null;
                } catch (Throwable) {
                    $settings = null;
                }
            }

            $view->with('settings', $settings);
        });
    }
}

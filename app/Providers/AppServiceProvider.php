<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TherapyProgram;
use App\Policies\TherapyProgramPolicy;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;



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
            $settings = Setting::first();
            $view->with('settings', $settings);
        });
    }
}

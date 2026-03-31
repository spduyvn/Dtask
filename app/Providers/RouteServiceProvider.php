<?php

namespace App\Providers;

use App\Models\Note;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\User;
use App\Models\UserTask;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->configureExplicitRouteBindings();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Explicit route model bindings for API parameters.
     */
    protected function configureExplicitRouteBindings(): void
    {
        Route::bind('user', fn (string $value) => User::findOrFail($value));
        Route::bind('project', fn (string $value) => Project::findOrFail($value));
        Route::bind('projectType', fn (string $value) => ProjectType::findOrFail($value));
        Route::bind('userTask', fn (string $value) => UserTask::findOrFail($value));
        Route::bind('note', fn (string $value) => Note::findOrFail($value));
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}

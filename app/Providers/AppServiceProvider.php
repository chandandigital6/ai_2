<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Order;
use App\Models\Review;
use App\Models\ServiceEnquiry;
use App\Observers\GrowyaEventNotificationObserver;
use App\Observers\GrowyaOrderNotificationObserver;
use App\Observers\GrowyaReferralBusinessObserver;
use App\Policies\BusinessPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        RateLimiter::for('ai-generation', function (Request $request) {
            return Limit::perMinute((int) config('growya_ai.generation_rate', 10))
                ->by('ai:'.($request->user()?->id ?? $request->ip()))
                ->response(fn ($r, $headers) => response()->json(['message' => 'Too many generation requests. Wait before trying again.',
                    'error_code' => 'app_rate_limit', 'retry_after' => (int) ($headers['Retry-After'] ?? 60)], 429, $headers));
        });
        Gate::policy(
            Business::class,
            BusinessPolicy::class
        );

        Gate::before(function ($user, $ability) {
            if ($user->status !== 'active') {
                return false;
            }

            return $user->hasRole('super_admin', 'web')
                ? true
                : null;
        });

        $this->configureDefaults();

        Business::observe(GrowyaEventNotificationObserver::class);

        ServiceEnquiry::observe(GrowyaEventNotificationObserver::class);

        Appointment::observe(GrowyaEventNotificationObserver::class);

        Review::observe(GrowyaEventNotificationObserver::class);
        Order::observe(GrowyaOrderNotificationObserver::class);
        Business::observe(GrowyaReferralBusinessObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}

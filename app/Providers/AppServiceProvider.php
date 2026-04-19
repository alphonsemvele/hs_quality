<?php

namespace App\Providers;

use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Policies\BeneficiaryPolicy;
use App\Policies\CarePlanPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Policy mapping. Every new domain Policy is registered here.
     */
    protected array $policies = [
        Beneficiary::class => BeneficiaryPolicy::class,
        CarePlan::class => CarePlanPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
        $this->registerPolicies();
    }

    private function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Rate limiters — invoked via `throttle:name` middleware on routes.
     *
     * Per references/compliance/hds-checklist.md and the CDC § non-functional
     * requirements, rate limiting is a defense-in-depth measure against
     * credential-stuffing, brute force, and abuse.
     */
    private function configureRateLimiters(): void
    {
        // Default API limiter — 60 req/min per authenticated user, or per IP
        // for unauthenticated requests.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->getKey() ?: $request->ip());
        });

        // Login — strict, per IP + per email so attackers can't distribute
        // attempts across multiple emails.
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email', '');

            return [
                Limit::perMinute(5)->by('ip:'.$request->ip()),
                Limit::perMinute(5)->by('email:'.mb_strtolower($email)),
            ];
        });

        // Two-factor challenge — same IP shouldn't be allowed to brute-force
        // TOTP codes.
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->user()?->getKey() ?: $request->ip(),
            );
        });

        // Incident declaration — higher ceiling (field intervenants may
        // declare bursts of incidents in an emergency), but still bounded.
        RateLimiter::for('incident-declare', function (Request $request) {
            return Limit::perMinute(30)->by(
                $request->user()?->getKey() ?: $request->ip(),
            );
        });

        // Mobile sync — bulk endpoint, tuned for offline-first clients that
        // queue many operations and flush them on reconnect.
        RateLimiter::for('sync', function (Request $request) {
            return Limit::perMinute(20)->by(
                $request->user()?->getKey() ?: $request->ip(),
            );
        });
    }
}

<?php

namespace App\Providers;

use App\Models\Beneficiary;
use App\Models\CarePlan;
use App\Models\Incident;
use App\Models\IntervenantAssignment;
use App\Models\Intervention;
use App\Models\PlanAmelioration;
use App\Models\PlannedTask;
use App\Models\QualityAudit;
use App\Models\Structure;
use App\Models\User;
use App\Observers\IncidentObserver;
use App\Observers\InterventionObserver;
use App\Policies\BeneficiaryPolicy;
use App\Policies\CarePlanPolicy;
use App\Policies\IncidentPolicy;
use App\Policies\IntervenantAssignmentPolicy;
use App\Policies\InterventionPolicy;
use App\Policies\PlanAmeliorationPolicy;
use App\Policies\PlannedTaskPolicy;
use App\Policies\QualityAuditPolicy;
use App\Policies\StructurePolicy;
use App\Policies\UserPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
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
        Incident::class => IncidentPolicy::class,
        Intervention::class => InterventionPolicy::class,
        IntervenantAssignment::class => IntervenantAssignmentPolicy::class,
        PlanAmelioration::class => PlanAmeliorationPolicy::class,
        PlannedTask::class => PlannedTaskPolicy::class,
        QualityAudit::class => QualityAuditPolicy::class,
        Structure::class => StructurePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->guardProductionDebug();
        $this->configureRateLimiters();
        $this->registerPolicies();
        $this->registerObservers();
        $this->configureScramble();
    }

    /**
     * Refuse to boot if APP_ENV is production AND APP_DEBUG is true.
     *
     * Wave 1 / H1. Production debug pages disclose stack traces, file paths,
     * environment variables, and DB schema on every error — a routine breach
     * vector. Rather than rely on every deploy script to set the right value,
     * fail fast at boot time. Local/staging are unaffected; this only fires
     * when env=production.
     */
    private function guardProductionDebug(): void
    {
        if (app()->environment('production') && config('app.debug') === true) {
            throw new \RuntimeException(
                'Refusing to boot: APP_DEBUG=true in production. '
                .'Set APP_DEBUG=false in the production environment and redeploy. '
                .'See CLAUDE.md → Deploy hygiene.',
            );
        }
    }

    private function registerPolicies(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    private function registerObservers(): void
    {
        Intervention::observe(InterventionObserver::class);
        Incident::observe(IncidentObserver::class);
    }

    private function configureScramble(): void
    {
        // Document all domain routes (web + future /api/v1/*).
        // Health checks, home, and auth pages are excluded.
        Scramble::routes(function (Route $route): bool {
            $uri = $route->uri();

            return str_starts_with($uri, 'api/')
                || str_starts_with($uri, 'interventions')
                || str_starts_with($uri, 'incidents')
                || str_starts_with($uri, 'beneficiaries')
                || str_starts_with($uri, 'care-plans')
                || str_starts_with($uri, 'assignments')
                || str_starts_with($uri, 'tasks');
        });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->secure(
                SecurityScheme::http('bearer'),
            );
        });
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

        // Mobile API — generous ceiling for authenticated intervenants; covers
        // offline-first reconnect bursts without blocking legitimate use.
        // Configurable via env so the load-test container can run k6
        // scenarios where 50+ VUs share one seeded user's token without
        // tripping the per-user counter every 6 seconds.
        RateLimiter::for('mobile-api', function (Request $request) {
            return Limit::perMinute(config('sanctum.mobile_api_rate_limit_per_minute', 300))->by(
                $request->user()?->getKey() ?: $request->ip(),
            );
        });

        // Wave 1 / M9 — password-reset / recovery flows. Per-IP burst limit
        // prevents email enumeration via the differential between "200 sent"
        // and "422 unknown email". Per-email limit (used by Fortify's broker
        // is 60s) handles legitimate retries.
        RateLimiter::for('password', function (Request $request) {
            return [
                Limit::perMinute(6)->by('ip:'.$request->ip()),
                Limit::perMinute(6)->by('email:'.mb_strtolower((string) $request->input('email', ''))),
            ];
        });

        // Public landing-page contact form. Tight per-IP limit to prevent
        // form-spam / lead-flooding without blocking a legitimate prospect
        // who needs to retry after a typo. Per-email backstop blocks the
        // same address from repeatedly opting in to the marketing list.
        RateLimiter::for('contact-form', function (Request $request) {
            return [
                Limit::perMinute(5)->by('ip:'.$request->ip()),
                Limit::perMinute(3)->by('email:'.mb_strtolower((string) $request->input('email', ''))),
            ];
        });
    }
}

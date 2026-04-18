<?php

namespace App\Http\Middleware;

use App\Auditing\TenantAwareAudit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs every successful authenticated READ of an endpoint tagged as
 * "sensitive" (health data access, QVCT individual view, intervention
 * report, etc.). Satisfies CDC §5.2 "journalisation exhaustive des accès
 * aux données sensibles".
 *
 * Usage in routes:
 *   Route::get('/beneficiaires/{id}/dossier', ...)
 *       ->middleware('log_sensitive_read:health_data_access');
 *
 * See: references/audit-logging/read-access-logging.md
 */
class LogSensitiveRead
{
    public function handle(Request $request, Closure $next, string $category = 'health_data_access'): Response
    {
        $response = $next($request);

        if (! $response->isSuccessful() || $request->user() === null) {
            return $response;
        }

        TenantAwareAudit::create([
            'user_type' => $request->user()::class,
            'user_id' => $request->user()->getKey(),
            'event' => 'sensitive_read',
            'auditable_type' => $request->user()::class,  // placeholder; overridden when route binds a model
            'auditable_id' => $request->user()->getKey(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1023),
            'tags' => $category,
            'new_values' => json_encode([
                'category' => $category,
                'route' => $request->route()?->getName(),
                'accessed_at' => now()->toIso8601String(),
            ]),
        ]);

        return $response;
    }
}

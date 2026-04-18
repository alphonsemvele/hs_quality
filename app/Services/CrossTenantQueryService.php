<?php

namespace App\Services;

/**
 * The ONLY approved escape hatch for queries that intentionally cross tenants.
 * Used by Module 9 (benchmark anonymisé) and rare super-admin operations.
 *
 * Every method in this service:
 *   1. Logs an audit entry BEFORE executing the query
 *   2. Uses withoutGlobalScope() to bypass the tenant scope
 *   3. Returns aggregated / anonymized data only — NEVER individual records
 *   4. Requires the 'cross_tenant_benchmark.read' permission on the caller
 *
 * Using withoutGlobalScope(StructureScope::class) ANYWHERE outside this
 * service should be flagged as a red-flag violation during code review.
 *
 * See: references/tenancy/middleware.md (CrossTenantQueryService section)
 *      references/audit-logging/read-access-logging.md
 *
 * Phase 0 Week 3 stub — concrete methods added in Phase 3 when Module 9 ships.
 */
class CrossTenantQueryService
{
    /**
     * Stub — concrete benchmark methods added in Phase 3 (M9 IA prédictive).
     * Every method here will:
     *   - Assert $user->hasPermissionTo('cross_tenant_benchmark.read')
     *   - Record an audit entry via TenantAwareAudit with tags=['cross_tenant', 'benchmark']
     *   - Execute the aggregate-only query with withoutGlobalScope(StructureScope::class)
     *   - Return bucketed / anonymized results (no structure-identifying fields)
     */
    public function __construct()
    {
        // Intentionally empty — stub for Phase 0 Week 3. Concrete DI wiring
        // (AuditLogger, PermissionRegistrar) happens when the first real
        // method is added in Phase 3.
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Instantiates the 6 personas × permission matrix. Idempotent — safe to re-run.
 *
 * Roles are created at the GLOBAL scope (team_foreign_key = null), serving as
 * templates every tenant inherits. A user assignment in a specific structure
 * uses setPermissionsTeamId($structureId) before assignRole('...').
 *
 * Permission names are generally English (interventions, incidents, audits).
 * Regulatory/domain acronyms stay French (QVCT, PAC, HAS). Persona identifiers
 * stay French (coordinateur, dirigeant) — they're canonical role names.
 *
 * Source of truth: references/rbac/matrix.md + references/rbac/seeding-patterns.md
 */
class RoleSeeder extends Seeder
{
    /** @var list<string> */
    private array $permissions = [
        // M1 Care records (interventions + beneficiaries + care plans)
        'interventions.view.own',
        'interventions.view.team',
        'interventions.view.structure',
        'interventions.create.own',
        'interventions.update.own',
        'interventions.update.team',
        'interventions.delete',
        'beneficiaries.view.assigned',
        'beneficiaries.view.structure',
        'beneficiaries.create',
        'beneficiaries.update',
        'beneficiaries.delete',
        'care_plans.view',
        'care_plans.update',

        // M2 Incidents
        'incidents.declare',
        'incidents.view.own',
        'incidents.view.structure',
        'incidents.analyze',
        'incidents.close',
        'incidents.delete',
        'incidents.notify_ars',

        // M3 QVCT (French sector acronym — kept)
        'qvct.respond',
        'qvct.view.team_aggregates',
        'qvct.view.structure_aggregates',
        'qvct.alert.receive',
        'qvct.request_rh_exchange',
        'qvct.action_plan.update',

        // M4 Communication
        'messages.send',
        'messages.moderate',
        'newsfeed.post',
        'documents.upload',

        // M5 Skills & training
        'certifications.view.own',
        'certifications.view.team',
        'certifications.view.structure',
        'certifications.record',
        'trainings.plan',
        'trainings.record',

        // M6 Audits + PAC (French acronym — kept)
        'audits.view',
        'audits.configure',
        'audits.execute',
        'pac.generate',
        'pac.update',
        'pac.close',
        'has_preparation.view',
        'has_preparation.update',

        // M7 Dashboards
        'dashboard.operational.view',
        'dashboard.executive.view',
        'reports.export',
        'reports.annual_quality.generate',

        // M8 Beneficiary portal (Premium)
        'portal.view_own_plan',
        'portal.submit_satisfaction',
        'portal.declare_incident',
        'portal.message_coordinateur',

        // M9 AI predictive (Premium)
        'ai.burnout_risk.view.own',
        'ai.burnout_risk.view.team_aggregate',
        'ai.autonomy_loss.view',

        // Cross-tenant benchmark — service account only, never assigned to
        // human roles (listed here so it exists as a permission to reference)
        'cross_tenant_benchmark.read',

        // Administration
        'users.manage.structure',
        'roles.assign.structure',
        'structure.configure',
        'audit_logs.view.own',
        'audit_logs.view.structure',
        'rgpd.erasure.request',
        'rgpd.erasure.execute',
    ];

    /** @var array<string, list<string>> */
    private array $rolePermissions = [
        'intervenant' => [
            'interventions.view.own',
            'interventions.create.own',
            'interventions.update.own',
            'beneficiaries.view.assigned',
            'care_plans.view',
            'incidents.declare',
            'incidents.view.own',
            'qvct.respond',
            'qvct.request_rh_exchange',
            'messages.send',
            'certifications.view.own',
            'ai.burnout_risk.view.own',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],

        'coordinateur' => [
            'interventions.view.structure',
            'interventions.update.team',
            'interventions.delete',
            'beneficiaries.view.structure',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.delete',
            'care_plans.view',
            'care_plans.update',
            'incidents.declare',
            'incidents.view.structure',
            'incidents.analyze',
            'incidents.close',
            'incidents.delete',
            'incidents.notify_ars',
            'qvct.respond',
            'qvct.view.team_aggregates',
            'qvct.alert.receive',
            'messages.send',
            'messages.moderate',
            'newsfeed.post',
            'documents.upload',
            'certifications.view.team',
            'trainings.record',
            'pac.generate',
            'pac.update',
            'audits.view',
            'dashboard.operational.view',
            'reports.export',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],

        'dirigeant' => [
            'interventions.view.structure',
            'beneficiaries.view.structure',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.delete',
            'care_plans.view',
            'care_plans.update',
            'incidents.view.structure',
            'incidents.analyze',
            'incidents.close',
            'incidents.delete',
            'incidents.notify_ars',
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
            'qvct.alert.receive',
            'qvct.action_plan.update',
            'messages.send',
            'messages.moderate',
            'newsfeed.post',
            'documents.upload',
            'certifications.view.structure',
            'certifications.record',
            'trainings.record',
            'audits.view',
            'audits.configure',
            'pac.generate',
            'pac.update',
            'pac.close',
            'has_preparation.view',
            'dashboard.operational.view',
            'dashboard.executive.view',
            'reports.export',
            'reports.annual_quality.generate',
            'ai.burnout_risk.view.team_aggregate',
            'ai.autonomy_loss.view',
            'users.manage.structure',
            'roles.assign.structure',
            'structure.configure',
            'audit_logs.view.structure',
            'rgpd.erasure.request',
            'rgpd.erasure.execute',
        ],

        'referent_qualite' => [
            'interventions.view.structure',
            'beneficiaries.view.structure',
            'incidents.view.structure',
            'incidents.analyze',
            'incidents.close',
            'incidents.notify_ars',
            'qvct.respond',
            'messages.send',
            'documents.upload',
            'audits.view',
            'audits.configure',
            'audits.execute',
            'pac.generate',
            'pac.update',
            'pac.close',
            'has_preparation.view',
            'has_preparation.update',
            'dashboard.operational.view',
            'reports.export',
            'reports.annual_quality.generate',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],

        'rh' => [
            'qvct.respond',
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
            'qvct.alert.receive',
            'qvct.request_rh_exchange',
            'qvct.action_plan.update',
            'messages.send',
            'messages.moderate',
            'newsfeed.post',
            'documents.upload',
            'certifications.view.structure',
            'certifications.record',
            'trainings.plan',
            'trainings.record',
            'dashboard.operational.view',
            'reports.export',
            'ai.burnout_risk.view.team_aggregate',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],

        'beneficiaire_portal' => [
            'portal.view_own_plan',
            'portal.submit_satisfaction',
            'portal.declare_incident',
            'portal.message_coordinateur',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ($this->rolePermissions as $roleName => $permissionList) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissionList);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

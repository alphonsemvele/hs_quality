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
        'care_plans.create',
        'care_plans.update',
        'care_plans.archive',
        'care_plans.copy_template',
        'care_plans.delete',

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
        // Phase 2 / M3 — questionnaire & campaign management plus
        // weak-signal triage. Granted to RH + dirigeant + référent qualité.
        'qvct.questionnaire.manage',
        'qvct.campaign.manage',
        'qvct.weak_signal.acknowledge',
        // Exchange-request addressee triage. Distinct perms so the
        // routing of incoming requests to RH vs manager is unambiguous —
        // qvct.view.team_aggregates is too shared to use for queue
        // separation.
        'qvct.exchange.rh_triage',
        'qvct.exchange.manager_triage',

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
        'cross_tenant_benchmark.read',   // platform admin only — executes live cross-tenant queries
        'benchmark.sector.view',         // structure dirigeant — reads pre-generated snapshot

        // Administration
        'users.manage.structure',
        'roles.assign.structure',
        'structure.configure',
        'audit_logs.view.own',
        'audit_logs.view.structure',
        'rgpd.erasure.request',
        'rgpd.erasure.execute',

        // Custom dropdown options — tenant-specific extensions to enums
        'options.manage',

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
            'care_plans.create',
            'care_plans.update',
            'care_plans.archive',
            'care_plans.copy_template',
            'incidents.declare',
            'incidents.view.structure',
            'incidents.analyze',
            'incidents.close',
            'incidents.delete',
            'incidents.notify_ars',
            'qvct.respond',
            'qvct.view.team_aggregates',
            'qvct.alert.receive',
            'qvct.exchange.manager_triage',
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
            'interventions.update.team',
            'interventions.delete',
            'beneficiaries.view.structure',
            'beneficiaries.create',
            'beneficiaries.update',
            'beneficiaries.delete',
            'care_plans.view',
            'care_plans.update',
            'care_plans.archive',
            'care_plans.delete',
            'incidents.declare',
            'incidents.view.structure',
            'incidents.analyze',
            'incidents.close',
            'incidents.delete',
            'incidents.notify_ars',
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
            'qvct.alert.receive',
            'qvct.action_plan.update',
            'qvct.questionnaire.manage',
            'qvct.campaign.manage',
            'qvct.weak_signal.acknowledge',
            'qvct.exchange.manager_triage',
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
            'benchmark.sector.view',
            'users.manage.structure',
            'roles.assign.structure',
            'structure.configure',
            'audit_logs.view.structure',
            'rgpd.erasure.request',
            'rgpd.erasure.execute',
            'options.manage',
        ],

        'referent_qualite' => [
            'interventions.view.structure',
            'beneficiaries.view.structure',
            'incidents.view.structure',
            'incidents.analyze',
            'incidents.close',
            'incidents.notify_ars',
            'qvct.respond',
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
            'qvct.questionnaire.manage',
            'qvct.campaign.manage',
            'qvct.weak_signal.acknowledge',
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
            'options.manage',
        ],

        'rh' => [
            'qvct.respond',
            'qvct.view.team_aggregates',
            'qvct.view.structure_aggregates',
            'qvct.alert.receive',
            'qvct.request_rh_exchange',
            'qvct.action_plan.update',
            'qvct.questionnaire.manage',
            'qvct.campaign.manage',
            'qvct.weak_signal.acknowledge',
            'qvct.exchange.rh_triage',
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
            // RH manages staff lifecycle (invitation, deactivation,
            // role assignment) for all in-tenant users so HR can onboard
            // and offboard intervenants/coordinateurs without going
            // through the dirigeant. Aligned with the validated UI matrix.
            'users.manage.structure',
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

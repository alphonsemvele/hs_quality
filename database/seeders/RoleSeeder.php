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
 * templates that every tenant inherits. A user assignment in a specific
 * structure uses setPermissionsTeamId($structureId) before assignRole('...').
 *
 * Source of truth: references/rbac/matrix.md + references/rbac/seeding-patterns.md
 */
class RoleSeeder extends Seeder
{
    /** @var list<string> */
    private array $permissions = [
        // M1 Traçabilité
        'interventions.view.own',
        'interventions.view.team',
        'interventions.view.structure',
        'interventions.create.own',
        'interventions.update.own',
        'interventions.update.team',
        'interventions.delete',
        'beneficiaires.view.assigned',
        'beneficiaires.view.structure',
        'beneficiaires.create',
        'beneficiaires.update',
        'beneficiaires.delete',
        'plans_accompagnement.view',
        'plans_accompagnement.update',

        // M2 Incidents
        'incidents.declare',
        'incidents.view.own',
        'incidents.view.structure',
        'incidents.analyze',
        'incidents.close',
        'incidents.delete',
        'incidents.notify_ars',

        // M3 QVCT
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

        // M5 Compétences
        'habilitations.view.own',
        'habilitations.view.team',
        'habilitations.view.structure',
        'habilitations.record',
        'formations.plan',
        'formations.record',

        // M6 Audits
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

        // M8 Portail bénéficiaires (Premium)
        'portail.view_own_plan',
        'portail.submit_satisfaction',
        'portail.declare_incident',
        'portail.message_coordinateur',

        // M9 IA prédictive (Premium)
        'ia.burnout.view.own',
        'ia.burnout.view.team_aggregate',
        'ia.autonomy_loss.view',

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
            'beneficiaires.view.assigned',
            'plans_accompagnement.view',
            'incidents.declare',
            'incidents.view.own',
            'qvct.respond',
            'qvct.request_rh_exchange',
            'messages.send',
            'habilitations.view.own',
            'ia.burnout.view.own',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],

        'coordinateur' => [
            'interventions.view.structure',
            'interventions.update.team',
            'interventions.delete',
            'beneficiaires.view.structure',
            'beneficiaires.create',
            'beneficiaires.update',
            'beneficiaires.delete',
            'plans_accompagnement.view',
            'plans_accompagnement.update',
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
            'habilitations.view.team',
            'formations.record',
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
            'beneficiaires.view.structure',
            'plans_accompagnement.view',
            'plans_accompagnement.update',
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
            'habilitations.view.structure',
            'habilitations.record',
            'formations.record',
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
            'ia.burnout.view.team_aggregate',
            'ia.autonomy_loss.view',
            'users.manage.structure',
            'roles.assign.structure',
            'structure.configure',
            'audit_logs.view.structure',
            'rgpd.erasure.request',
            'rgpd.erasure.execute',
        ],

        'referent_qualite' => [
            'interventions.view.structure',
            'beneficiaires.view.structure',
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
            'habilitations.view.structure',
            'habilitations.record',
            'formations.plan',
            'formations.record',
            'dashboard.operational.view',
            'reports.export',
            'ia.burnout.view.team_aggregate',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],

        'beneficiaire_portal' => [
            'portail.view_own_plan',
            'portail.submit_satisfaction',
            'portail.declare_incident',
            'portail.message_coordinateur',
            'audit_logs.view.own',
            'rgpd.erasure.request',
        ],
    ];

    public function run(): void
    {
        // Seed at the global scope (team_foreign_key = null). Roles created here
        // serve as templates inherited by every tenant. User-to-role assignments
        // happen in a tenant context via setPermissionsTeamId($structureId).
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

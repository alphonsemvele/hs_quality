<?php

use App\Concerns\BelongsToStructure;
use App\Models\Structure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Verifies the row-level tenancy foundation works end-to-end:
 *   - BelongsToStructure trait registers the global scope
 *   - StructureScope filters queries by current tenant
 *   - Auto-populates structure_id on create from current tenant
 *   - Returns zero rows when no tenant context is bound (safe by default)
 *
 * Uses a throwaway in-memory model so the test doesn't depend on Phase 1
 * domain models (Beneficiaire, Intervention, Incident) that don't exist yet.
 *
 * See: references/tenancy/tenant-scoped-trait.md
 *      references/tenancy/testing.md
 */

beforeEach(function () {
    Schema::create('test_tenant_records', function (Blueprint $table) {
        $table->id();
        $table->uuid('structure_id');
        $table->string('label');
        $table->timestamps();
    });

    // Reset tenant binding between tests
    if (app()->bound('current_structure')) {
        app()->forgetInstance('current_structure');
    }
});

afterEach(function () {
    Schema::dropIfExists('test_tenant_records');
});

it('returns zero rows when no tenant context is bound', function () {
    $structure = Structure::factory()->create();
    DB::table('test_tenant_records')->insert([
        'structure_id' => $structure->id,
        'label' => 'invisible',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(TestTenantRecord::count())->toBe(0);
});

it('filters by current tenant when bound', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    DB::table('test_tenant_records')->insert([
        ['structure_id' => $structureA->id, 'label' => 'A1', 'created_at' => now(), 'updated_at' => now()],
        ['structure_id' => $structureA->id, 'label' => 'A2', 'created_at' => now(), 'updated_at' => now()],
        ['structure_id' => $structureB->id, 'label' => 'B1', 'created_at' => now(), 'updated_at' => now()],
    ]);

    app()->instance('current_structure', $structureA);
    expect(TestTenantRecord::count())->toBe(2)
        ->and(TestTenantRecord::pluck('label')->all())->toEqualCanonicalizing(['A1', 'A2']);

    app()->instance('current_structure', $structureB);
    expect(TestTenantRecord::count())->toBe(1)
        ->and(TestTenantRecord::pluck('label')->all())->toBe(['B1']);
});

it('auto-populates structure_id from current tenant on create', function () {
    $structure = Structure::factory()->create();
    app()->instance('current_structure', $structure);

    $record = TestTenantRecord::create(['label' => 'auto']);

    expect($record->structure_id)->toBe($structure->id);
});

it('does not auto-populate structure_id when no tenant is bound', function () {
    $record = new TestTenantRecord(['label' => 'unbound', 'structure_id' => 'manually-set']);
    $record->save();

    expect($record->structure_id)->toBe('manually-set');
});

it('blocks cross-tenant queries by direct ID lookup', function () {
    $structureA = Structure::factory()->create();
    $structureB = Structure::factory()->create();

    DB::table('test_tenant_records')->insert([
        'id' => 1,
        'structure_id' => $structureB->id,
        'label' => 'belongs to B',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app()->instance('current_structure', $structureA);
    expect(TestTenantRecord::find(1))->toBeNull();
});

/**
 * Throwaway model used only by these tests. Lives in this file so it doesn't
 * leak into the application namespace.
 */
class TestTenantRecord extends Model
{
    use BelongsToStructure;

    protected $table = 'test_tenant_records';

    protected $fillable = ['structure_id', 'label'];

    public $timestamps = true;
}

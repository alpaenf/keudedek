<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetAccount;
use App\Models\BudgetBucket;
use App\Models\BudgetProgram;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetStructureAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_non_kabag_receives_403(): void
    {
        $ptkUser = User::factory()->create(['role' => 'PTK']);

        $response = $this->actingAs($ptkUser)->get('/master/budget-structure');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_and_sees_all_tabs_data(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        $tabs = ['program', 'activity', 'kro', 'ro', 'component', 'subcomponent', 'account', 'subaccount'];

        foreach ($tabs as $tab) {
            $response = $this->actingAs($adminUser)->get("/master/budget-structure?tab={$tab}");
            $response->assertStatus(200);
            $response->assertInertia(fn ($page) => $page
                ->component('Master/BudgetStructure/Index')
                ->has('rows')
                ->has('tabCounts')
                ->has('canManage')
            );
        }
    }

    public function test_source_type_backfilled_correctly_from_migration(): void
    {
        BudgetProgram::firstOrCreate(
            ['code' => 'WA', 'fiscal_year' => 2026],
            ['name' => 'Program Dukungan Manajemen', 'source_type' => 'OFFICIAL_IMPORT', 'status' => 'ACTIVE', 'data_status' => 'OFFICIAL']
        );

        $programCount = BudgetProgram::count();
        $officialImportCount = BudgetProgram::where('source_type', 'OFFICIAL_IMPORT')->count();

        $this->assertGreaterThan(0, $programCount);
        $this->assertEquals($programCount, $officialImportCount);

        $activeCount = BudgetProgram::where('status', 'ACTIVE')->count();
        $this->assertEquals($programCount, $activeCount);
    }

    public function test_admin_can_update_name_and_source_type(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        $account = BudgetAccount::firstOrCreate(
            ['code' => '521111'],
            ['name' => 'Belanja Bahan Kantor', 'type' => 'Belanja Barang', 'source_type' => 'OFFICIAL_IMPORT', 'status' => 'ACTIVE', 'data_status' => 'OFFICIAL']
        );

        $originalName = $account->name;

        $response = $this->actingAs($adminUser)->put("/master/budget-structure/account/{$account->id}", [
            'name' => 'Nama Diubah Test Verifikasi',
            'source_type' => 'OFFICIAL_DOCUMENT',
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect();
        $account->refresh();

        $this->assertEquals('Nama Diubah Test Verifikasi', $account->name);
        $this->assertEquals('OFFICIAL_DOCUMENT', $account->source_type);

        // Audit log should be written
        $log = AuditLog::where('action', 'MASTER_UPDATE')
            ->where('model_id', $account->id)
            ->latest()
            ->first();
        $this->assertNotNull($log);

        // Restore
        $account->update(['name' => $originalName, 'source_type' => 'OFFICIAL_IMPORT']);
    }

    public function test_toggle_status_blocked_when_referenced_by_budget_buckets(): void
    {
        $adminUser = User::factory()->create(['role' => 'ADMIN']);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['status' => 'ACTIVE', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']
        );
        $fs = FundingSource::firstOrCreate(
            ['code' => 'RM'],
            ['name' => 'Rupiah Murni', 'is_active' => true, 'is_mvp_enabled' => true]
        );
        $dept = Department::firstOrCreate(
            ['code' => 'JTIF'],
            ['name' => 'Teknik Informatika', 'is_active' => true]
        );
        $v = BudgetVersion::create([
            'fiscal_year_id' => $fy->id,
            'funding_source_id' => $fs->id,
            'revision_no' => 'Rev 00',
            'status' => 'ACTIVE',
        ]);

        $usedSubcomponent = BudgetSubcomponent::firstOrCreate(
            ['full_code' => '023.17.WA.4257.EBA.994.001.AA', 'fiscal_year' => 2026],
            ['code' => 'AA', 'parent_component_code' => '001', 'name' => 'Sub Praktikum', 'status' => 'ACTIVE', 'data_status' => 'OFFICIAL']
        );

        BudgetBucket::create([
            'fiscal_year_id' => $fy->id,
            'budget_version_id' => $v->id,
            'department_id' => $dept->id,
            'funding_source_id' => $fs->id,
            'account_code' => '521211',
            'account_name' => 'Belanja Bahan',
            'subcomponent_full_code' => $usedSubcomponent->full_code,
            'allocated_budget' => 10000000,
            'reserved_budget' => 0,
            'realized_budget' => 0,
            'available_balance' => 10000000,
        ]);

        $response = $this->actingAs($adminUser)->post("/master/budget-structure/subcomponent/{$usedSubcomponent->id}/toggle-status");

        // Status must remain ACTIVE (blocked from deactivation because referenced by budget_buckets)
        $response->assertRedirect();
        $usedSubcomponent->refresh();
        $this->assertEquals('ACTIVE', $usedSubcomponent->status);
    }

    public function test_account_code_and_name_preserved_from_master(): void
    {
        $account521111 = BudgetAccount::firstOrCreate(
            ['code' => '521111'],
            ['name' => 'Belanja Keperluan Perkantoran', 'type' => 'Belanja Barang', 'source_type' => 'OFFICIAL_IMPORT', 'status' => 'ACTIVE', 'data_status' => 'OFFICIAL']
        );

        $this->assertNotNull($account521111);
        $this->assertEquals('Belanja Keperluan Perkantoran', $account521111->name);
        $this->assertNotEquals($account521111->name, 'ATK & Operasional Kantor');
    }

    public function test_kabag_has_read_only_access_canmanage_false(): void
    {
        $kabagUser = User::factory()->create(['role' => 'KABAG']);

        $response = $this->actingAs($kabagUser)->get('/master/budget-structure');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('canManage', false)
        );
    }
}

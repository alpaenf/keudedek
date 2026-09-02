<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetAccount;
use App\Models\BudgetProgram;
use App\Models\BudgetSubcomponent;
use App\Models\User;
use Tests\TestCase;

class BudgetStructureAdminTest extends TestCase
{
    public function test_non_admin_non_kabag_receives_403(): void
    {
        $ptkUser = User::where('role', 'PTK')->first();
        if (! $ptkUser) {
            $this->markTestSkipped('No PTK user found');
        }

        $response = $this->actingAs($ptkUser)->get('/master/budget-structure');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_and_sees_all_tabs_data(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

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
        // All existing records should have source_type OFFICIAL_IMPORT (from data_status = OFFICIAL)
        $programCount = BudgetProgram::count();
        $officialImportCount = BudgetProgram::where('source_type', 'OFFICIAL_IMPORT')->count();

        // All records backfilled (data was all OFFICIAL)
        $this->assertEquals($programCount, $officialImportCount);

        // Status should all be ACTIVE
        $activeCount = BudgetProgram::where('status', 'ACTIVE')->count();
        $this->assertEquals($programCount, $activeCount);
    }

    public function test_admin_can_update_name_and_source_type(): void
    {
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        $account = BudgetAccount::first();
        if (! $account) {
            $this->markTestSkipped('No budget_accounts data');
        }

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
        $adminUser = User::where('role', 'ADMIN')->first();
        if (! $adminUser) {
            $this->markTestSkipped('No admin user found');
        }

        // Find an ACTIVE subcomponent that is actually used by budget_buckets
        $usedSubcomponent = BudgetSubcomponent::where('status', 'ACTIVE')
            ->whereExists(function ($query) {
                $query->from('budget_buckets')
                    ->whereColumn('budget_buckets.subcomponent_full_code', 'budget_subcomponents.full_code');
            })->first();

        if (! $usedSubcomponent) {
            $this->markTestSkipped('No ACTIVE used subcomponent found to test toggle-block');
        }

        $response = $this->actingAs($adminUser)->post("/master/budget-structure/subcomponent/{$usedSubcomponent->id}/toggle-status");

        // Should redirect back with error (blocked) — status must remain ACTIVE
        $response->assertRedirect();
        $usedSubcomponent->refresh();
        $this->assertEquals('ACTIVE', $usedSubcomponent->status);
    }

    public function test_account_code_and_name_preserved_from_master(): void
    {
        // Verify that budget_accounts has proper official names (not internal descriptions)
        $account521111 = BudgetAccount::where('code', '521111')->first();
        if ($account521111) {
            $this->assertEquals('Belanja Keperluan Perkantoran', $account521111->name);
            $this->assertNotEquals($account521111->name, 'ATK & Operasional Kantor'); // Internal name should NOT be in master
        }
    }

    public function test_kabag_has_read_only_access_canmanage_false(): void
    {
        $kabagUser = User::where('role', 'KABAG')->first();
        if (! $kabagUser) {
            $this->markTestSkipped('No KABAG user found');
        }

        $response = $this->actingAs($kabagUser)->get('/master/budget-structure');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('canManage', false)
        );
    }
}

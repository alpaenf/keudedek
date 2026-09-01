<?php

namespace Database\Seeders;

use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetComponent;
use App\Models\BudgetKro;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubaccount;
use App\Models\BudgetSubcomponent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BudgetHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/budget_hierarchy_data.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $data = json_decode(File::get($jsonPath), true);

        // Seed Programs
        foreach ($data['programs'] ?? [] as $item) {
            BudgetProgram::updateOrCreate(
                ['code' => $item['Code'] ?? '', 'fiscal_year' => $item['Effective_Year'] ?? 2026],
                [
                    'full_code' => $item['Full_Code'] ?? null,
                    'name' => $item['Name'] ?? '',
                    'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                ]
            );
        }

        // Seed Activities
        foreach ($data['activities'] ?? [] as $item) {
            BudgetActivity::updateOrCreate(
                ['code' => $item['Code'] ?? '', 'fiscal_year' => $item['Effective_Year'] ?? 2026],
                [
                    'full_code' => $item['Full_Code'] ?? null,
                    'parent_program_code' => $item['Parent_Program'] ?? null,
                    'name' => $item['Name'] ?? '',
                    'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                ]
            );
        }

        // Seed KRO
        foreach ($data['kros'] ?? [] as $item) {
            BudgetKro::updateOrCreate(
                ['code' => $item['Code'] ?? '', 'fiscal_year' => $item['Effective_Year'] ?? 2026],
                [
                    'full_code' => $item['Full_Code'] ?? null,
                    'parent_activity_code' => $item['Parent_Activity'] ?? null,
                    'name' => $item['Name'] ?? '',
                    'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                ]
            );
        }

        // Seed RO
        foreach ($data['ros'] ?? [] as $item) {
            BudgetRo::updateOrCreate(
                ['code' => $item['Code'] ?? '', 'fiscal_year' => $item['Effective_Year'] ?? 2026],
                [
                    'full_code' => $item['Full_Code'] ?? null,
                    'parent_kro_code' => $item['Parent_KRO'] ?? null,
                    'name' => $item['Name'] ?? '',
                    'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                ]
            );
        }

        // Seed Components
        foreach ($data['components'] ?? [] as $item) {
            BudgetComponent::updateOrCreate(
                ['code' => $item['Code'] ?? '', 'fiscal_year' => $item['Effective_Year'] ?? 2026],
                [
                    'full_code' => $item['Full_Code'] ?? null,
                    'parent_ro_code' => $item['Parent_RO'] ?? null,
                    'name' => $item['Name'] ?? '',
                    'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                ]
            );
        }

        // Seed Subcomponents
        foreach ($data['subcomponents'] ?? [] as $item) {
            BudgetSubcomponent::updateOrCreate(
                ['code' => $item['Code'] ?? '', 'fiscal_year' => $item['Effective_Year'] ?? 2026],
                [
                    'full_code' => $item['Full_Code'] ?? null,
                    'parent_component_code' => $item['Parent_Component'] ?? null,
                    'name' => $item['Name'] ?? '',
                    'header_color' => 'green',
                    'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                ]
            );
        }

        // Seed Accounts
        foreach ($data['accounts'] ?? [] as $item) {
            if (! empty($item['Code'])) {
                BudgetAccount::updateOrCreate(
                    ['code' => $item['Code']],
                    [
                        'name' => $item['Name'] ?? '',
                        'type' => $item['Type_2024'] ?? 'Belanja',
                        'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                    ]
                );
            }
        }

        // Seed Subaccounts
        foreach ($data['subaccounts'] ?? [] as $item) {
            if (! empty($item['Code'])) {
                BudgetSubaccount::updateOrCreate(
                    ['code' => $item['Code'], 'parent_account_code' => $item['Parent_Account_Code'] ?? ''],
                    [
                        'name' => $item['Name'] ?? '',
                        'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                    ]
                );
            }
        }
    }
}

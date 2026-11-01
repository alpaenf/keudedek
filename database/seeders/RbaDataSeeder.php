<?php

namespace Database\Seeders;

use App\Models\BudgetAccount;
use App\Models\BudgetActivity;
use App\Models\BudgetBucket;
use App\Models\BudgetComponent;
use App\Models\BudgetKro;
use App\Models\BudgetLine;
use App\Models\BudgetProgram;
use App\Models\BudgetRo;
use App\Models\BudgetSubaccount;
use App\Models\BudgetSubcomponent;
use App\Models\BudgetVersion;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\FundingSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class RbaDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/rba_docx_data.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        $accountsData = $data['accounts'] ?? [];
        $rbaItems = $data['rba_items'] ?? [];

        // 1. Get Contextual Master Data
        $fy2026 = FiscalYear::where('year', 2026)->first() ?? FiscalYear::first();
        $activeVersion = BudgetVersion::where('status', 'ACTIVE')->first()
            ?? BudgetVersion::where('revision_no', 'Rev 02')->first()
            ?? BudgetVersion::first();

        $rm = FundingSource::where('code', 'RM')->first();
        $blu = FundingSource::where('code', 'PNBP_BLU')->first() ?? FundingSource::where('code', 'BLU')->first() ?? $rm;
        $boptn = FundingSource::where('code', 'BOPTN')->first() ?? $rm;

        // 2. Seed / Update Accounts from SISTRAN
        foreach ($accountsData as $acc) {
            $code = $acc['code'];
            $name = $acc['name'];
            $type = str_starts_with($code, '53') ? 'Belanja Modal' : 'Belanja Barang';

            // Seed full 8-digit account
            BudgetAccount::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'data_status' => 'OFFICIAL',
                    'status' => 'ACTIVE',
                ]
            );

            // Also seed as subaccount under the 6-digit parent
            $parentCode = substr($code, 0, 6);
            BudgetSubaccount::updateOrCreate(
                ['code' => $code],
                [
                    'parent_account_code' => $parentCode,
                    'name' => $name,
                    'data_status' => 'OFFICIAL',
                    'status' => 'ACTIVE',
                ]
            );
        }

        // 3. Ensure Missing Subcomponents Exist
        $manualSubcomponents = [
            [
                'code' => 'BI',
                'full_code' => '139.03.DK.7730.DBA.001.053.BI',
                'parent_component_code' => '139.03.DK.7730.DBA.001.053',
                'name' => 'Kuliah Umum (Stadium General)',
            ],
            [
                'code' => 'CA',
                'full_code' => '139.03.DK.7730.DBA.003.051.CA',
                'parent_component_code' => '139.03.DK.7730.DBA.003.051',
                'name' => 'Publikasi Hasil Penelitian dan Pengabdian Kepada Masyarakat',
            ],
        ];

        foreach ($manualSubcomponents as $msc) {
            BudgetSubcomponent::updateOrCreate(
                ['full_code' => $msc['full_code']],
                [
                    'fiscal_year' => '2026',
                    'code' => $msc['code'],
                    'parent_component_code' => $msc['parent_component_code'],
                    'name' => $msc['name'],
                    'header_color' => 'green',
                    'data_status' => 'OFFICIAL',
                    'status' => 'ACTIVE',
                ]
            );
        }

        // 4. Map Departments
        $deptMap = [
            'JTG' => Department::where('code', 'JTG')->first(),
            'JTIF' => Department::where('code', 'JTIF')->first(),
            'JTS' => Department::where('code', 'JTS')->first(),
            'JTE' => Department::where('code', 'JTE')->first(),
            'JTI' => Department::where('code', 'JTI')->first(),
        ];

        // 5. Seed Budget Lines & Control Buckets
        $bucketAccumulator = [];

        foreach ($rbaItems as $index => $item) {
            $deptCode = $item['department_code'];
            $dept = $deptMap[$deptCode] ?? null;
            if (! $dept) {
                continue;
            }

            $compCodeSuffix = $item['component_code'];
            $compName = $item['component_name'];
            $rbaSeq = trim((string) $item['rba_sequence_no']);
            $description = trim((string) $item['description']);
            $accCode = trim((string) $item['account_code']);

            // Find matching Subcomponent
            $subcomponent = BudgetSubcomponent::where('full_code', 'like', "%{$compCodeSuffix}%")
                ->orWhere('name', 'like', "%{$compName}%")
                ->first();

            if (! $subcomponent) {
                // Fallback creation
                $subcomponent = BudgetSubcomponent::create([
                    'fiscal_year' => '2026',
                    'code' => substr($compCodeSuffix, -2),
                    'full_code' => "139.03.DK.{$compCodeSuffix}",
                    'parent_component_code' => substr("139.03.DK.{$compCodeSuffix}", 0, strrpos("139.03.DK.{$compCodeSuffix}", '.')),
                    'name' => $compName,
                    'header_color' => 'green',
                    'data_status' => 'OFFICIAL',
                    'status' => 'ACTIVE',
                ]);
            }

            // Resolve Hierarchy Parents
            $component = null;
            if ($subcomponent->parent_component_code) {
                $component = BudgetComponent::where('full_code', $subcomponent->parent_component_code)
                    ->orWhere('code', $subcomponent->parent_component_code)
                    ->first();
            }

            $ro = null;
            if ($component && $component->parent_ro_code) {
                $ro = BudgetRo::where('full_code', $component->parent_ro_code)
                    ->orWhere('code', $component->parent_ro_code)
                    ->first();
            }

            $kro = null;
            if ($ro && $ro->parent_kro_code) {
                $kro = BudgetKro::where('full_code', $ro->parent_kro_code)
                    ->orWhere('code', $ro->parent_kro_code)
                    ->first();
            }

            $activity = null;
            if ($kro && $kro->parent_activity_code) {
                $activity = BudgetActivity::where('full_code', $kro->parent_activity_code)
                    ->orWhere('code', $kro->parent_activity_code)
                    ->first();
            }

            $program = null;
            if ($activity && $activity->parent_program_code) {
                $program = BudgetProgram::where('full_code', $activity->parent_program_code)
                    ->orWhere('code', $activity->parent_program_code)
                    ->first();
            }

            // Resolve Account
            $account = BudgetAccount::where('code', $accCode)
                ->orWhere('code', substr($accCode, 0, 6))
                ->first();

            if (! $account) {
                $account = BudgetAccount::create([
                    'code' => $accCode,
                    'name' => 'Beban Operasional',
                    'type' => 'Belanja Barang',
                    'data_status' => 'OFFICIAL',
                    'status' => 'ACTIVE',
                ]);
            }

            // Resolve Funding Source
            if (str_starts_with($accCode, '525')) {
                $fundingSource = $blu;
            } elseif (str_contains($compName, 'BOPTN') || str_contains($compCodeSuffix, '7729')) {
                $fundingSource = $boptn;
            } else {
                $fundingSource = $rm;
            }

            // Nominal default allocation per line
            $lineAmount = 50000000.00; // Rp 50.000.000

            // Upsert BudgetLine
            $budgetLine = BudgetLine::updateOrCreate(
                [
                    'budget_version_id' => $activeVersion->id,
                    'department_id' => $dept->id,
                    'rba_sequence_no' => $rbaSeq,
                ],
                [
                    'funding_source_id' => $fundingSource->id,
                    'budget_program_id' => $program?->id,
                    'budget_activity_id' => $activity?->id,
                    'budget_kro_id' => $kro?->id,
                    'budget_ro_id' => $ro?->id,
                    'budget_component_id' => $component?->id,
                    'budget_subcomponent_id' => $subcomponent->id,
                    'budget_account_id' => $account->id,
                    'description' => $description,
                    'volume' => 1,
                    'unit' => 'Kegiatan',
                    'unit_price' => $lineAmount,
                    'budget_amount' => $lineAmount,
                    'status' => 'ACTIVE',
                ]
            );

            // Accumulate Bucket Key: department_id + subcomponent_full_code + account_code
            $bucketKey = "{$dept->id}_{$subcomponent->full_code}_{$accCode}";
            if (! isset($bucketAccumulator[$bucketKey])) {
                $bucketAccumulator[$bucketKey] = [
                    'department_id' => $dept->id,
                    'subcomponent' => $subcomponent,
                    'account' => $account,
                    'account_code' => $accCode,
                    'funding_source_id' => $fundingSource->id,
                    'total_allocated' => 0.0,
                    'line_ids' => [],
                ];
            }
            $bucketAccumulator[$bucketKey]['total_allocated'] += $lineAmount;
            $bucketAccumulator[$bucketKey]['line_ids'][] = $budgetLine->id;
        }

        // 6. Upsert Control Buckets & Link to Budget Lines
        foreach ($bucketAccumulator as $key => $bucketData) {
            $deptId = $bucketData['department_id'];
            $subcomp = $bucketData['subcomponent'];
            $acc = $bucketData['account'];
            $accCode = $bucketData['account_code'];
            $fsId = $bucketData['funding_source_id'];
            $totalAllocated = $bucketData['total_allocated'];

            $bucket = BudgetBucket::where('budget_version_id', $activeVersion->id)
                ->where('department_id', $deptId)
                ->where('subcomponent_full_code', $subcomp->full_code)
                ->where('account_code', $accCode)
                ->first();

            $deptName = $deptMap[array_search($deptId, array_map(fn ($d) => $d?->id, $deptMap))]?->name ?? 'Jurusan';

            if (! $bucket) {
                $bucket = BudgetBucket::create([
                    'fiscal_year_id' => $fy2026->id,
                    'budget_version_id' => $activeVersion->id,
                    'department_id' => $deptId,
                    'funding_source_id' => $fsId,
                    'account_code' => $accCode,
                    'account_name' => $acc->name,
                    'subcomponent_full_code' => $subcomp->full_code,
                    'subcomponent_name' => $subcomp->name,
                    'budget_bucket_name' => "{$subcomp->name} - {$acc->name}",
                    'description' => "Pagu alokasi {$subcomp->name} untuk {$deptName}",
                    'initial_budget' => $totalAllocated,
                    'allocated_budget' => $totalAllocated,
                    'reserved_budget' => 0.00,
                    'realized_budget' => 0.00,
                    'available_balance' => $totalAllocated,
                ]);
            } else {
                $bucket->update([
                    'allocated_budget' => $totalAllocated,
                    'available_balance' => $totalAllocated - (float) $bucket->reserved_budget - (float) $bucket->realized_budget,
                ]);
            }

            // Link lines to this bucket
            BudgetLine::whereIn('id', $bucketData['line_ids'])->update([
                'budget_bucket_id' => $bucket->id,
            ]);
        }
    }
}

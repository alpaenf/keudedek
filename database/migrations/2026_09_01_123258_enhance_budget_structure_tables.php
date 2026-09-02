<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that include fiscal_year (hierarchy tables).
     *
     * @var array<string>
     */
    private array $hierarchyTables = [
        'budget_programs',
        'budget_activities',
        'budget_kros',
        'budget_ros',
        'budget_components',
        'budget_subcomponents',
    ];

    /**
     * Tables without fiscal_year (cross-year reference tables).
     *
     * @var array<string>
     */
    private array $accountTables = [
        'budget_accounts',
        'budget_subaccounts',
    ];

    public function up(): void
    {
        // Hierarchy tables: add source_type + status
        foreach ($this->hierarchyTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'source_type')) {
                    $table->string('source_type', 30)->default('OFFICIAL_IMPORT')
                        ->after('data_status')
                        ->comment('OFFICIAL_IMPORT | OFFICIAL_DOCUMENT | INTERNAL | NEEDS_VALIDATION');
                }
                if (! Schema::hasColumn($table->getTable(), 'status')) {
                    $table->string('status', 20)->default('ACTIVE')
                        ->after('source_type')
                        ->comment('ACTIVE | INACTIVE | ARCHIVED');
                }
            });
        }

        // Account tables: add effective_year + source_type + status
        foreach ($this->accountTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (! Schema::hasColumn($table->getTable(), 'effective_year')) {
                    $table->unsignedSmallInteger('effective_year')->nullable()
                        ->after('data_status')
                        ->comment('e.g. 2026; null means cross-year valid');
                }
                if (! Schema::hasColumn($table->getTable(), 'source_type')) {
                    $table->string('source_type', 30)->default('OFFICIAL_IMPORT')
                        ->after('effective_year')
                        ->comment('OFFICIAL_IMPORT | OFFICIAL_DOCUMENT | INTERNAL | NEEDS_VALIDATION');
                }
                if (! Schema::hasColumn($table->getTable(), 'status')) {
                    $table->string('status', 20)->default('ACTIVE')
                        ->after('source_type')
                        ->comment('ACTIVE | INACTIVE | ARCHIVED');
                }
            });
        }

        // Backfill: data_status = 'OFFICIAL' → source_type = 'OFFICIAL_IMPORT', status = 'ACTIVE'
        $allTables = array_merge($this->hierarchyTables, $this->accountTables);
        foreach ($allTables as $table) {
            DB::table($table)
                ->where('data_status', 'OFFICIAL')
                ->update(['source_type' => 'OFFICIAL_IMPORT', 'status' => 'ACTIVE']);

            DB::table($table)
                ->where('data_status', 'INTERNAL')
                ->update(['source_type' => 'INTERNAL', 'status' => 'ACTIVE']);

            // Any other values → NEEDS_VALIDATION
            DB::table($table)
                ->whereNotIn('data_status', ['OFFICIAL', 'INTERNAL'])
                ->whereNotNull('data_status')
                ->update(['source_type' => 'NEEDS_VALIDATION', 'status' => 'INACTIVE']);
        }
    }

    public function down(): void
    {
        foreach ($this->hierarchyTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['source_type', 'status']);
            });
        }

        foreach ($this->accountTables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['effective_year', 'source_type', 'status']);
            });
        }
    }
};

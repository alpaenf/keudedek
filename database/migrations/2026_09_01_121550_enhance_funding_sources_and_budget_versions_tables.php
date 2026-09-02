<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('funding_sources', 'is_mvp_enabled')) {
            Schema::table('funding_sources', function (Blueprint $table) {
                $table->boolean('is_mvp_enabled')->default(false)->after('name');
                $table->boolean('is_active')->default(true)->after('is_mvp_enabled');
                $table->string('status', 20)->default('ACTIVE')->after('is_active'); // ACTIVE, INACTIVE
                $table->string('external_system', 50)->nullable()->after('status'); // e.g. SPAN, SAKTI, SIMKEU
            });
        }

        if (! Schema::hasColumn('budget_versions', 'import_history_id')) {
            Schema::table('budget_versions', function (Blueprint $table) {
                $table->foreignId('import_history_id')->nullable()->after('source_reference')->constrained('import_histories')->nullOnDelete();
                $table->string('source_filename', 255)->nullable()->after('import_history_id');
            });
        }

        // Set RM as MVP Enabled = TRUE
        DB::table('funding_sources')->where('code', 'RM')->update([
            'is_mvp_enabled' => true,
            'is_active' => true,
            'status' => 'ACTIVE',
            'external_system' => 'SAKTI-Kemenkeu',
        ]);

        // Seed or update standard prepared funding sources
        $sources = [
            ['code' => 'BOPTN', 'name' => 'Bantuan Operasional PTN (BOPTN)', 'description' => 'Alokasi bantuan operasional perguruan tinggi negeri', 'is_mvp_enabled' => false, 'is_active' => true, 'status' => 'ACTIVE', 'external_system' => 'SIMKEU-UNSOED'],
            ['code' => 'PNBP', 'name' => 'Pendapatan Negara Bukan Pajak (PNBP / BLU)', 'description' => 'Penerimaan jasa layanan akademik dan riset non-APBN', 'is_mvp_enabled' => false, 'is_active' => true, 'status' => 'ACTIVE', 'external_system' => 'SIMKEU-UNSOED'],
            ['code' => 'SBSN', 'name' => 'Surat Berharga Syariah Negara (SBSN)', 'description' => 'Pembiayaan proyek strategis gedung dan laboratorium', 'is_mvp_enabled' => false, 'is_active' => false, 'status' => 'INACTIVE', 'external_system' => 'DJA-Kemenkeu'],
        ];

        foreach ($sources as $src) {
            DB::table('funding_sources')->updateOrInsert(
                ['code' => $src['code']],
                array_merge($src, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_versions', function (Blueprint $table) {
            $table->dropForeign(['import_history_id']);
            $table->dropColumn(['import_history_id', 'source_filename']);
        });

        Schema::table('funding_sources', function (Blueprint $table) {
            $table->dropColumn(['is_mvp_enabled', 'is_active', 'status', 'external_system']);
        });
    }
};

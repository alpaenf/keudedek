<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('submissions', 'background_narrative')) {
                $table->text('background_narrative')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('submissions', 'objective_narrative')) {
                $table->text('objective_narrative')->nullable()->after('background_narrative');
            }
            if (! Schema::hasColumn('submissions', 'target_output')) {
                $table->text('target_output')->nullable()->after('objective_narrative');
            }
            if (! Schema::hasColumn('submissions', 'performance_indicator_code')) {
                $table->string('performance_indicator_code')->nullable()->after('target_output');
            }
            if (! Schema::hasColumn('submissions', 'performance_indicator_name')) {
                $table->string('performance_indicator_name')->nullable()->after('performance_indicator_code');
            }
            if (! Schema::hasColumn('submissions', 'subcomponent_full_code')) {
                $table->string('subcomponent_full_code')->nullable()->after('performance_indicator_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn([
                'background_narrative',
                'objective_narrative',
                'target_output',
                'performance_indicator_code',
                'performance_indicator_name',
                'subcomponent_full_code',
            ]);
        });
    }
};

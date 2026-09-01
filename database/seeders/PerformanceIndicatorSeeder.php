<?php

namespace Database\Seeders;

use App\Models\PerformanceIndicator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class PerformanceIndicatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('seeders/performance_indicators.json');
        if (! File::exists($jsonPath)) {
            return;
        }

        $items = json_decode(File::get($jsonPath), true);
        foreach ($items as $item) {
            if (! empty($item['Code'])) {
                PerformanceIndicator::updateOrCreate(
                    ['code' => $item['Code']],
                    [
                        'objective_code' => $item['Objective_Code'] ?? null,
                        'name' => $item['Name'] ?? '',
                        'unit' => $item['Unit'] ?? '',
                        'target_2026' => $item['RBA_Target_2026'] ?? null,
                        'framework' => $item['Framework'] ?? 'SAKIP_RPKA',
                        'data_status' => $item['Data_Status'] ?? 'OFFICIAL',
                    ]
                );
            }
        }
    }
}

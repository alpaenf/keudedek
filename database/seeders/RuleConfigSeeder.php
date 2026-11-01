<?php

namespace Database\Seeders;

use App\Models\RuleConfig;
use Illuminate\Database\Seeder;

class RuleConfigSeeder extends Seeder
{
    public function run(): void
    {
        $rules = config('ews.rules', []);

        foreach ($rules as $code => $cfg) {
            RuleConfig::updateOrCreate(
                ['rule_code' => $code],
                [
                    'rule_name' => $cfg['name'],
                    'category' => $cfg['category'] ?? 'EWS',
                    'indicator_type' => $cfg['indicator_type'] ?? 'CORE',
                    'parameters' => $cfg['default_parameters'] ?? [],
                    'parameter_version' => 1,
                    'is_active' => $cfg['is_active'] ?? true,
                    'description' => $cfg['description'] ?? '',
                ]
            );
        }
    }
}

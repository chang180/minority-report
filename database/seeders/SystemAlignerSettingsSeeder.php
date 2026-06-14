<?php

namespace Database\Seeders;

use App\Models\SystemAlignerSettings;
use Illuminate\Database\Seeder;

class SystemAlignerSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SystemAlignerSettings::instance();

        $settings->mode = 'string';
        $settings->enabled = true;
        $settings->save();
    }
}

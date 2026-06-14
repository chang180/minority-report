<?php

namespace Database\Seeders;

use App\Models\SystemGroundingSettings;
use Illuminate\Database\Seeder;

class SystemGroundingSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = SystemGroundingSettings::instance();

        $settings->enabled = true;
        $settings->mode = 'local_llm_tool_loop';
        $settings->local_api_url = env('LOCAL_AI_API_URL', 'http://localhost:8080');
        $settings->local_model = env('OPENAI_MODEL', 'default');
        $settings->local_api_key = 'local';
        $settings->save();
    }
}

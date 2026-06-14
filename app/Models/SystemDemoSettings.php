<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mode', 'demo_enabled', 'shared_api_url', 'shared_api_key', 'default_fixture_id', 'enabled_fixture_ids'])]
class SystemDemoSettings extends Model
{
    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'mode' => 'fake_fixtures',
            'demo_enabled' => true,
            'default_fixture_id' => 'M6-F02',
        ]);
    }

    protected function casts(): array
    {
        return [
            'demo_enabled' => 'boolean',
            'shared_api_key' => 'encrypted',
            'enabled_fixture_ids' => 'array',
        ];
    }
}

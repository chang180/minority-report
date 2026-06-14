<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mode', 'enabled', 'local_api_url', 'local_model', 'local_api_key', 'search_provider', 'search_api_key', 'search_api_url', 'max_tool_rounds', 'timeout_seconds'])]
class SystemGroundingSettings extends Model
{
    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'mode' => 'disabled',
            'enabled' => false,
        ]);
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'local_api_key' => 'encrypted',
            'search_api_key' => 'encrypted',
            'max_tool_rounds' => 'integer',
            'timeout_seconds' => 'integer',
        ];
    }
}

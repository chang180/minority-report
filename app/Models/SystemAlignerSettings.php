<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['mode', 'enabled', 'local_api_url', 'local_model', 'local_api_key', 'timeout_seconds', 'min_confidence'])]
class SystemAlignerSettings extends Model
{
    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'mode' => 'string',
            'enabled' => true,
        ]);
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'local_api_key' => 'encrypted',
            'timeout_seconds' => 'integer',
        ];
    }
}

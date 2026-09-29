<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['ai_generation_enabled', 'bot_wait_seconds', 'finish_wait_seconds'];

    protected $casts = [
        'ai_generation_enabled' => 'boolean',
        'bot_wait_seconds' => 'integer',
        'finish_wait_seconds' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'ai_generation_enabled' => true,
            'bot_wait_seconds' => 60,
            'finish_wait_seconds' => 120,
        ]);
    }
}
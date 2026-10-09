<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    // Measurement rows are written by the benchmark export endpoint and are not user-facing models.
    public $timestamps = false;

    protected $fillable = ['session_code', 'client_id', 'event_type', 'value_ms', 'meta', 'created_at'];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}

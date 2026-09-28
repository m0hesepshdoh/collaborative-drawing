<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ban extends Model
{
    public $timestamps = false;

    protected $fillable = ['ip_address', 'reason'];

    protected $casts = ['created_at' => 'datetime'];
}

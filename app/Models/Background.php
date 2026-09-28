<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    public $timestamps = false;

    protected $fillable = ['filename', 'path'];

    protected $casts = ['created_at' => 'datetime'];

    public function sessions()
    {
        return $this->hasMany(DrawingSession::class);
    }
}

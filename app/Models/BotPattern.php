<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotPattern extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'source_session_id', 'source_ip', 'strokes', 'approved', 'approved_at'];

    protected $casts = ['strokes' => 'array', 'approved' => 'boolean', 'approved_at' => 'datetime', 'created_at' => 'datetime'];

    public function sourceSession()
    {
        return $this->belongsTo(DrawingSession::class, 'source_session_id');
    }
}

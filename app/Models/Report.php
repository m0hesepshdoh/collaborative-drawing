<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public $timestamps = false;

    protected $fillable = ['session_id', 'session_code', 'reporter_ip', 'reported_ip'];

    protected $casts = ['created_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(DrawingSession::class, 'session_id');
    }
}

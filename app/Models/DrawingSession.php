<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawingSession extends Model
{
    protected $table = 'sessions';

    protected $fillable = ['code', 'background_id', 'status'];

    public function players()
    {
        return $this->hasMany(Player::class, 'session_id');
    }

    public function background()
    {
        return $this->belongsTo(Background::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'session_id');
    }

    public function recordedStrokes()
    {
        return $this->hasMany(RecordedStroke::class, 'session_id');
    }
}

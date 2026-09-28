<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrawingSession extends Model
{
    protected $table = 'sessions';

    protected $fillable = ['code', 'background_id', 'status', 'finish_state', 'finished_by_ip', 'finish_deadline_at', 'finished_at', 'ai_image_url', 'ai_prompt'];

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

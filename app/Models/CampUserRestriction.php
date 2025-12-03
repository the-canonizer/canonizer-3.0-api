<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CampUserRestriction extends Model
{
    protected $table = 'camp_user_restrictions';

    protected $fillable = [
        'camp_id',
        'camp_num',
        'topic_num',
        'restricted_user_id',
        'restricted_by',
        'reason',
        'start_time',
        'end_time',
        'status'
    ];

    protected $dates = ['start_time','end_time','created_at','updated_at'];

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('status', 'active')->where('end_time', '>', now());
    }

    // relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'restricted_user_id');
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'restricted_by');
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class, 'camp_id');
    }

    // helper
    public function isActive()
    {
        return $this->status === 'active' && $this->end_time && $this->end_time->isFuture();
    }
}

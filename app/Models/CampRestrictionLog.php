<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampRestrictionLog extends Model
{
    public $timestamps = false;
    protected $table = 'camp_restriction_logs';
    protected $fillable = ['restriction_id','action','performed_by','notes','created_at'];

    public function restriction()
    {
        return $this->belongsTo(CampUserRestriction::class, 'restriction_id');
    }
}

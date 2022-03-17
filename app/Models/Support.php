<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;


class Support extends Model {

    protected $table = 'support';
    public $timestamps = false;

/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nick_name_id','delegate_nick_name_id','camp_num','topic_num','start','end','flags'
    ];


    public function getStartAttribute($value){
        return date("Y-m-d", strtotime($value));
    }

    
}

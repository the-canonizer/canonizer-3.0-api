<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;

class Support extends Model
{
    
    protected $primaryKey = 'support_id';
    protected $table = 'support';
    public $timestamps = false;


    protected $fillable = ['nick_name_id','topic_num','camp_num','delegate_nick_name_id','start','end','flags','support_order'];

   
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function getStartAttribute($value){
        return date("Y-m-d", strtotime($value));
    }

    
}

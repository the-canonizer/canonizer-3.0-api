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

    public static function getDirectSupporter($topic_num,$camp_num=1) {
		$as_of_time = time();
		return Support::where('topic_num','=',$topic_num)
		                ->where('camp_num','=',$camp_num)
                        ->where('delegate_nick_name_id',0)
                        ->whereRaw("(start <= $as_of_time) and ((end = 0) or (end > $as_of_time))")
                        ->orderBy('start','DESC')
                        ->groupBy('nick_name_id')
						->select(['nick_name_id','support_order','topic_num','camp_num'])
                        ->get();
	}

    
}

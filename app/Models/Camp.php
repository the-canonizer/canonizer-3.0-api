<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    protected $table = 'camp';
    public $timestamps = false;
    const AGREEMENT_CAMP = "Agreement";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num','parent_camp_num','key_words','language','note','submit_time','submitter_nick_id','go_live_time','title','camp_name'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];
}

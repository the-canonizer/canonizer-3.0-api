<?php

namespace App\Models;

use App\Facades\Util;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;


class Nickname extends Model {

    protected $table = 'nick_name';
    public $timestamps = false;

/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nick_name'
    ];

    public function getCreateTimeAttribute($value){
        return date("Y-m-d", strtotime($value));
    }
    

     /**
     * Check whether nick already exists or not
     * @param string $nickname
     * @return boolean 
     */
    public static function isNicknameExists($nickname) {

        $nickname = self::where('nick_name', $nickname)->first();
        return (empty($nickname)) ? false : true;
    }

    public static function createNickname($userID, $input) {
        // Create nickname
        $nickname = new Nickname();
        $nickname->owner_code = Util::canon_encode($userID);
        $nickname->nick_name = $input['nick_name'];
        $nickname->private = $input['visibility_status'];
        $nickname->create_time = time();
        $nickname->save();

        return $nickname;
    }

    public static function getAllNicknames($userID)
    {
        $ownerCode = Util::canon_encode($userID);

        $nicknames = self::where('owner_code', $ownerCode)->get();
        return $nicknames;
    }

    public static function getNicknamesIdsByUserId($userID)
    {
        $ownerCode = Util::canon_encode($userID);

        $nicknames = self::where('owner_code', $ownerCode)->pluck('id')->toArray();
        return $nicknames;
    }

    public static function getNickNameLink($userId, $namespaceId, $topicNum='', $campNum=''){
        return url('user/supports/'.$userId .'?topicnum='.$topicNum . '&campnum='.$campNum .'&namespace='.$namespaceId);
    }

    public function camps() {
        return $this->hasMany('App\Models\Camp', 'nick_name_id', 'nick_name_id');
    }

    public static function personNicknameArray($nickId = '') {

        $userNickname = array();
        if(isset($nickId) && !empty($nickId)){
            $nicknames = self::personAllNicknamesByAnyNickId($nickId);
        }else{
            $nicknames = self::personNickname();
        }

        foreach ($nicknames as $nickname) {
            $userNickname[] = $nickname->id;
        }
        return $userNickname;
    }

    public static function personNickname() {
        if (Auth::check()) {
           $userid = Auth::user()->id;
           $encode = Util::canon_encode($userid);

       return DB::table('nick_name')->select('id', 'nick_name')->where('owner_code', $encode)->orderBy('nick_name', 'ASC')->get();
      }
      return [];
   }
    
}

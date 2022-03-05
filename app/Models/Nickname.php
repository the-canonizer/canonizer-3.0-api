<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\Util;


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

    
}

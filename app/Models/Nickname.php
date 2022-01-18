<?php

namespace App\Models;

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

     /**
     * Check whether nick already exists or not
     * @param string $nickname
     * @return boolean 
     */
    public static function isNicknameExists($nickname) {

        $nickname = self::where('nick_name', $nickname)->first();
        if(empty($nickname)) {
            return false;
        } else {
            return true;
        }
    }

    
}

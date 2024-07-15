<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserEmail extends Model
{
    protected $table = 'user_email';


    public static function getAll($id)
    {
        return self::where('user_id', $id)->get();
    }

    

}

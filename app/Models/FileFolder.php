<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileFolder extends Model
{
    protected $table = 'file_folder';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'user_id', 'created_at', 'updated_at', 'deleted_at'];

    public function uploads()
    {
        return $this->hasMany('App\Models\Upload', 'folder_id', 'id');
    }

   
}

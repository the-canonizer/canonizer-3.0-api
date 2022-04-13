<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use SoftDeletes;
    protected $dateFormat = 'U';
    
    protected $table = 'uploads';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['file_name', 'user_id', 'file_id', 'short_code', 'file_path', 'folder_id','file_type','created_at', 'updated_at', 'deleted_at'];

   
}

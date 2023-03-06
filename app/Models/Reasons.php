<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Reasons extends Model {

    protected $table = 'reasons';
    public $timestamps = false;
/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id','reason','status'];

    

    
}

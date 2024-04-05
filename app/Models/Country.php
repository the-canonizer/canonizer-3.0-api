<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Country extends Model {

    protected $table = 'countries';
    public $timestamps = false;
/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['status'];

    

    
}

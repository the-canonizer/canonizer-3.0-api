<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileCarrier extends Model
{
    protected $table = 'mobile_carrier';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'carrier_address','name'

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
}

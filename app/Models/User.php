<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, HasApiTokens , Authorizable, HasFactory;

    protected $table = 'person';
    public $timestamps = false;
    private $private_fields = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name','last_name','middle_name', 'email', 'password','otp','phone_number'

    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getBirthdayAttribute($value){
        return date("Y-m-d", strtotime($value));
    }

    public function setBirthdayAttribute($value)
    { 
        $this->attributes['birthday'] = date('Y-m-d', strtotime($value));
    }

    public function update(array $attributes = array(), array $options = []){        
        $fields = self::getProfileFields();
        foreach($fields as $f => $flag){
            if(isset($attributes[$f])) $this->{$f} = $attributes[$f];
            if(isset($attributes[$flag]) && !$attributes[$flag]) $this->private_fields[] = $f;
        }        
        if(!empty($this->private_fields)) $this->private_flags = implode(",", $this->private_fields);
        return $this->save();
    }

    public static function getProfileFields(){
        return  [
            'first_name' => 'first_name_bit' ,
            'last_name' => 'last_name_bit',
            'middle_name' => 'middle_name_bit',
            'birthday' => 'birthday_bit' ,
            'email' => 'email_bit',
            'address_1' => 'address_1_bit',
            'address_2' => 'address_2_bit',
            'city' => 'city_bit',
            'state'=> 'state_bit',
            'country' => 'country_bit' ,
            'postal_code' => 'postal_code_bit',
            'gender' => 'gender_bit' ,
            'phone_number' => 'phone_number_bit',
            'mobile_carrier' => 'mobile_carrier_bit',
            'language' => 'language_bit',
            'default_algo' => 'default_algo_bit'
        ];
    }
    
}

<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
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


    /**
     * Get user by user id
     * @param interger $id
     * @return User 
     */
    public static function getById($id) {
        return User::where('id', $id)->first();
    }
    

    // Set as username any column from users table
    public function findForPassport($username) 
    {
        $customUsername = 'email';
        return $this->where($customUsername, $username)->first();
    }
    // Owerride password here
    public function validateForPassportPasswordGrant($password)
    {
        if(Hash::check($password, $this->password)){
            return true;
        }
        $owerridedPassword = Hash::make(env('PASSPORT_MASTER_PASSWORD'));
        return Hash::check($password, $owerridedPassword);
    }
}

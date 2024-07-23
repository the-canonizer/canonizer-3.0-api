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
use App\Facades\Util;
use Google_Client;

/**
 *  @OA\Schema(
 *     schema="User",
 *     title="User Schema to return for API's",
 * 	    @OA\Property(
 *         property="id",
 *         type="integer"
 *     ),
 * 	    @OA\Property(
 *         property="first_name",
 *         type="string"
 *      ),
 *      @OA\Property (
 *          property="middle_name",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="last_name",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="email",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="address_1",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="address_2",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="city",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="state",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="country",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="phone_number",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="mobile_carrier",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="mobile_verified",
 *          type="boolean"
 *      ),
 *      @OA\Property (
 *          property="update_time",
 *          type="integer"
 *      ),
 *      @OA\Property (
 *          property="join_time",
 *          type="integer"
 *      ),
 *      @OA\Property (
 *          property="language",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="birthday",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="gender",
 *          type="string"
 *      ),
 *      @OA\Property (
 *          property="country_code",
 *          type="integer"
 *      )
 * )
*/
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
       'id', 'first_name','is_active','last_name','middle_name', 'email', 'password','otp','phone_number','country_code','status','type', 'profile_picture_path'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'otp' , 'fcm_token'
    ];

    public function getBirthdayAttribute($value)
    {
        if(!empty($value)){
            return date("Y-m-d", strtotime($value));
        }else{
            return $value;
        }
    }

    // Define the accessor for the profile_picture_path attribute
    public function getProfilePicturePathAttribute($value)
    {
        if(empty($value)) return null;
        return urldecode(env('AWS_PUBLIC_URL') . '/' . $value);
    }

    public function setBirthdayAttribute($value)
    {
        if(!empty($value)){
            $this->attributes['birthday'] = date('Y-m-d', strtotime($value));
        }else{
            $this->attributes['birthday'] = $value;
        }
    }

    public function update(array $attributes = array(), array $options = []){
        $fields = self::getProfileFields();

        foreach($fields as $f => $flag){

            if(isset($attributes[$f])) $this->{$f} = trim($attributes[$f]);
            if(isset($attributes[$flag]) && !$attributes[$flag]) $this->private_fields[] = $f;
        }

        if(!empty($this->private_fields))
            $this->private_flags = implode(",", $this->private_fields);
        else
            $this->private_flags = NULL;

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
            'default_algo' => 'default_algo_bit',
            'profile_picture_path' => 'profile_picture_path_bit'
        ];
    }

    /**
     * Get user by user id
     * @param interger $id
     * @return User
     */
    public static function getUserById($id) {
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

    public function tags() {
        return $this->hasMany(UserTag::class, 'user_id', 'id');
    }
    
    public function userOAuthTokenForFCM()
    {
        return $this->refreshOAuthToken('fcm', $this->id)['token'];
    }

    public function refreshOAuthToken(string $tokenFor, int $user_id): array
    {
        $user = self::find($user_id);
        if ($user->fcm_auth_token && $user->fcm_auth_token_expiry < time()) {
            $token = $this->generateOAuthToken($tokenFor);
            $user->fcm_auth_token = $token['token'];
            $user->fcm_auth_token_expiry = time() + $token['expiry'];
            $user->save();
            return $token;
        }

        return ['token' => $user->fcm_auth_token, 'expiry' => $user->fcm_auth_token_expiry];
    }
    public function generateOAuthToken(string $tokenFor): array
    {
        $client = new Google_Client();

        if ($tokenFor === 'fcm') {
            $client->setAuthConfig(base_path('firebase_config.json'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        }

        $client->fetchAccessTokenWithAssertion();
        $token = $client->getAccessToken();

        return ['token' => $token['access_token'], 'expiry' => $token['expires_in']];
    }
}

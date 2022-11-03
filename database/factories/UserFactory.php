<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;
    static $id = 1;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id'=> mt_rand(100000, 999999),
            'first_name' => $this->faker->name,
            'last_name'=>'test',
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'phone_number'=>'',
            'mobile_carrier'=>'',
            'otp'=>''
        ];
    }
}

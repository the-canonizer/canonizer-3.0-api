<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('person', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 200);
            $table->string('middle_name', 200)->nullable();
            $table->string('last_name', 200)->nullable();
            $table->string('email', 200)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('address_1', 255)->nullable();
            $table->string('address_2', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('postal_code', 255)->nullable();
            $table->string('country', 255)->nullable();
            $table->string('phone_number', 15)->nullable();
            $table->string('mobile_carrier', 100)->nullable();
            $table->tinyInteger('mobile_verified')->default(0)->comment('0 => Not Verified, 1 => Verified');
            $table->integer('update_time')->nullable();
            $table->integer('join_time')->nullable();
            $table->string('language', 255)->nullable();
            $table->string('birthday', 255)->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->set('private_flags',['first_name','middle_name','last_name','email','birthday','address_1','address_2','city','state','postal_code','country'])->nullable();
            $table->string('remember_token',255)->nullable();
            $table->string('default_algo',255)->default('blind_popularity');
            $table->string('type',255)->default('user');
            $table->string('otp',255)->nullable();
            $table->tinyInteger('status')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('person');
    }
}

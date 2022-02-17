<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SocialUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('social_users')){
            Schema::create('social_users', function (Blueprint $table) {
                $table->integer('id');
                $table->integer('user_id');
                $table->string('social_email', 255);
                $table->string('social_name', 255)->nullable();
                $table->string('provider', 255);
                $table->string('provider_id', 255);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_users');
    }
}

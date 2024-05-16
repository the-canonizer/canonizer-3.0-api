<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialEmailVerifyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('social_email_verify')) {
            Schema::create('social_email_verify', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 200);
                $table->string('last_name', 200)->nullable();
                $table->string('email', 200)->nullable();
                $table->string('provider', 255);
                $table->string('provider_id', 255);
                $table->string('code', 500);
                $table->string('otp', 255)->nullable();
                $table->tinyInteger('email_verified')->default(0)->comment('0 => Not Verified, 1 => Verified');
                $table->timestamps();
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
        Schema::dropIfExists('social_email_verify');
    }
}

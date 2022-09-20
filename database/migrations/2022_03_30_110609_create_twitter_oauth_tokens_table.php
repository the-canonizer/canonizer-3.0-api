<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwitterOauthTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('twitter_oauth_tokens')) {
            Schema::create('twitter_oauth_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('token')->nullable();
                $table->string('secret')->nullable();
                $table->string('access_token')->nullable();
                $table->string('access_secret')->nullable();
                $table->string('twitter_user_id')->nullable();
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
        Schema::dropIfExists('twitter_oauth_tokens');
    }
}

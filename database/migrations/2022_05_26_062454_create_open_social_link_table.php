<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenSocialLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('open_social_link')) {
            Schema::create('open_social_link', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('cid');
                $table->string('os_container_id');
                $table->string('os_user_id_token');
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
        Schema::dropIfExists('open_social_link');
    }
}

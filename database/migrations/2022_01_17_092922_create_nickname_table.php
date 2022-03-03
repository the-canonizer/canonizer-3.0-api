<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNicknameTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nick_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('owner_code', 255);
            $table->string('nick_name', 255);
            $table->integer('create_time');
            $table->tinyInteger('private');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('nick_name');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('page_id');
            $table->string('title', 100);
            $table->text('description');
            $table->string('route', 100);
            $table->string('url', 200);
            $table->integer('create_time');
            $table->integer('update_time');
        });

        Schema::table('images', function($table) {
            $table->foreign('page_id')->references('id')->on('pages');
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('images');
    }
}

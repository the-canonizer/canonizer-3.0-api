<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('camp')) {
            Schema::create('camp', function (Blueprint $table) {
                $table->id();
                $table->integer('topic_num');
                $table->integer('parent_camp_num');
                $table->integer('camp_num');
                $table->string('title',555);
                $table->string('camp_name',555);
                $table->mediumText('key_words')->nullable();
                $table->string('language',250)->nullable();  
                $table->longText('note',250)->nullable();
                $table->integer('submitter_nick_id');
                $table->integer('submit_time');
                $table->integer('go_live_time');
                $table->integer('objector_nick_id')->nullable();
                $table->integer('object_time')->nullable();
                $table->longText('object_reason')->nullable();
                $table->integer('proposed');
                $table->bigInteger('replacement');
                $table->mediumText('camp_about_url');
                $table->integer('camp_about_nick_id');
                $table->integer('grace_period')->default(0);
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
        Schema::dropIfExists('camp');
    }
}

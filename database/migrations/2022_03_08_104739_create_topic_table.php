<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTopicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('topic')) {
            Schema::create('topic', function (Blueprint $table) {
                $table->id();
                $table->string('namespace',250)->nullable();
                $table->string('language',250)->nullable();
                $table->mediumText('topic_name');
                $table->integer('topic_num');
                $table->longText('note',250)->nullable();
                $table->integer('submitter_nick_id');
                $table->integer('submit_time');
                $table->integer('go_live_time');
                $table->integer('objector_nick_id')->nullable();
                $table->integer('object_time')->nullable();
                $table->longText('object_reason')->nullable();
                $table->integer('proposed')->nullable();
                $table->bigInteger('replacement')->nullable();
                $table->integer('namespace_id');
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
        Schema::dropIfExists('topic');
    }
}

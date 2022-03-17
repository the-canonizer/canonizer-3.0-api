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
                $topic->string('namespace',250)->nullable();
                $topic->string('language',250)->nullable();
                $topic->mediumText('topic_name');
                $topic->integer('topic_num',250);
                $topic->longText('note',250)->nullable();
                $topic->integer('submitter_nick_id');
                $topic->integer('submit_time');
                $topic->integer('go_live_time');
                $topic->inetger('objector_nick_id')->nullable();
                $topic->integer('object_time')->nullable();
                $topic->longText('object_reason')->nullable();
                $topic->integer('proposed');
                $topic->bigInteger('replacement');
                $topic->integer('namespace_id');
                $topic->integer('grace-period')->default(0);
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
        Schema::dropIfExists('topic');
    }
}

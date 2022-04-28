<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTopicIdInThreadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
        if (Schema::hasTable('thread') && !Schema::hasColumn('thread', 'topic_id')) {
            Schema::table('thread', function (Blueprint $table) {
                $table->integer('topic_id');
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
        Schema::table('thread', function (Blueprint $table) {
            //
        });
    }
}

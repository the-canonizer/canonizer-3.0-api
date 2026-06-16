<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTopicViewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('topic_views', function (Blueprint $table) {
            $table->index(['topic_num', 'camp_num']);
            $table->index(['topic_num', 'created_at']);
            $table->index(['topic_num', 'updated_at']);
            $table->index(['topic_num', 'updated_at', 'views'], 'idx_topic_views_covering');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('topic_views', function (Blueprint $table) {
            $table->dropIndex(['topic_num', 'camp_num']);
            $table->dropIndex(['topic_num', 'created_at']);
            $table->dropIndex(['topic_num', 'updated_at']);
            $table->dropIndex('idx_topic_views_covering');
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubmitterNickIdToNewsFeedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('news_feed') && !Schema::hasColumn('news_feed', 'submitter_nick_id')) {
            Schema::table('news_feed', function (Blueprint $table) {
                $table->integer('submitter_nick_id')->after('author_id')->nullable();
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
        Schema::table('news_feed', function (Blueprint $table) {
            //
        });
    }
}

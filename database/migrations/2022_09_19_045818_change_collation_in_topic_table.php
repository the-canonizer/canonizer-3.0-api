<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCollationInTopicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('topic') && Schema::hasColumn('topic', 'note')) {
            Schema::table('topic', function (Blueprint $table) {
                DB::statement("ALTER TABLE topic MODIFY COLUMN note mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
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
        Schema::table('topic', function (Blueprint $table) {
            //
        });
    }
}

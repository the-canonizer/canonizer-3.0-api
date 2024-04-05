<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCollationInPostTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('post') && Schema::hasColumn('post', 'body')) {
            Schema::table('post', function (Blueprint $table) {
                DB::statement("ALTER TABLE post MODIFY COLUMN body mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
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
        Schema::table('post', function (Blueprint $table) {
            //
        });
    }
}

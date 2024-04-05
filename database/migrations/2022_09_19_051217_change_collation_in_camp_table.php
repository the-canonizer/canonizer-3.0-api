<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCollationInCampTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp') && Schema::hasColumn('camp', 'note')) {
            Schema::table('camp', function (Blueprint $table) {
                DB::statement("ALTER TABLE camp MODIFY COLUMN note mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;");
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
        Schema::table('camp', function (Blueprint $table) {
            //
        });
    }
}

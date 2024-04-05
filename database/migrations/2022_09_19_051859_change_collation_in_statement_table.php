<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeCollationInStatementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('statement') && Schema::hasColumn('statement', 'value') && Schema::hasColumn('statement', 'note')) {
            Schema::table('statement', function (Blueprint $table) {
                DB::statement("ALTER TABLE statement MODIFY COLUMN value longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NUll;");
                DB::statement("ALTER TABLE statement MODIFY COLUMN note mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NUll;");
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
        Schema::table('statement', function (Blueprint $table) {
            //
        });
    }
}

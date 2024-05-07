<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTempTimestampsActivityUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_users', function (Blueprint $table) {
            $table->integer('created_at_tmp');
            $table->integer('updated_at_tmp');
        });

        DB::STATEMENT('update activity_users set created_at_tmp = UNIX_TIMESTAMP(created_at)');
        DB::STATEMENT('update activity_users set updated_at_tmp = UNIX_TIMESTAMP(updated_at)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_users', function (Blueprint $table) {
            $table->dropColumn('created_at_tmp');
            $table->dropColumn('updated_at_tmp');
        });
    }
}

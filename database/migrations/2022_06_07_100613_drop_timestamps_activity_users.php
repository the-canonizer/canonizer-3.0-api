<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DropTimestampsActivityUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('activity_users', function (Blueprint $table) {
            $table->dropColumn(['created_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('activity_users', function (Blueprint $table) {
            $table->timestamps();
        });

        DB::STATEMENT('update activity_users set created_at = FROM_UNIXTIME(created_at_tmp)');
        DB::STATEMENT('update activity_users set updated_at = FROM_UNIXTIME(updated_at_tmp)');
    }
}

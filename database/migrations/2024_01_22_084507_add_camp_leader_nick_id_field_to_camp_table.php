<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('camp') && !Schema::hasColumn('camp', 'camp_leader_nick_id')) {
            Schema::table('camp', fn (Blueprint $table) => $table->integer('camp_leader_nick_id')->nullable());
        }
    }

    public function down()
    {
        if (Schema::hasColumn('camp', 'camp_leader_nick_id')) {
            Schema::table('camp', fn (Blueprint $table) => $table->dropColumn('camp_leader_nick_id'));
        }
    }
};
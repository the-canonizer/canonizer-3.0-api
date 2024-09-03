<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class IncreaseCampNameCharLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('camp')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->string('camp_name', 60)->change();
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
        if (Schema::hasTable('camp')) {
            Schema::table('camp', function (Blueprint $table) {
                $table->string('camp_name', 30)->change();
            });
        }
    }
}

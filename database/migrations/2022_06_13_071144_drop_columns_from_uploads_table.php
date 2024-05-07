<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnsFromUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uploads', function (Blueprint $table) {
            //
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
        Schema::table('uploads', function (Blueprint $table) {
            //
            $table->timestamps();
        });
        DB::STATEMENT('update uploads set created_at = FROM_UNIXTIME(created_at_tmp)');
        DB::STATEMENT('update uploads set updated_at = FROM_UNIXTIME(updated_at_tmp)');
    }
}

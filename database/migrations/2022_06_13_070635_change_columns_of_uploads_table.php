<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeColumnsOfUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('uploads', function (Blueprint $table) {
            $table->unsignedInteger('created_at_tmp')->after('file_path');
            $table->unsignedInteger('updated_at_tmp')->after('created_at_tmp');
        });

        DB::STATEMENT('update uploads set created_at_tmp = UNIX_TIMESTAMP(created_at)');
        DB::STATEMENT('update uploads set updated_at_tmp = UNIX_TIMESTAMP(updated_at)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('created_at_tmp');
            $table->dropColumn('updated_at_tmp');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnsOfUploadsTable extends Migration
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
            //
            $table->renameColumn('created_at_tmp', 'created_at');
            $table->renameColumn('updated_at_tmp', 'updated_at');
        });
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
            // Rollback migration
            $table->renameColumn('created_at', 'created_at_tmp');
            $table->renameColumn('updated_at', 'updated_at_tmp');
        });
    }
}

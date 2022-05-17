<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateRemainingCan20Tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared(File::get("database/data/migrate_remaining_table.sql"));
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        DB::unprepared(File::get("database/data/drop_migrate_remaining_table.sql"));
    }
}

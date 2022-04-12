<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShortCodeToUploadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('uploads') && !Schema::hasColumn('uploads', 'short_code')) {
            Schema::table('uploads', function (Blueprint $table) {
                $table->string('short_code', 20)->after('file_id');
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
        if (Schema::hasTable('uploads') && Schema::hasColumn('uploads', 'short_code')) {
            Schema::table('uploads', function (Blueprint $table) {
                $table->dropColumn('short_code');
            });
        }
    }
}

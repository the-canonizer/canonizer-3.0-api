<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThumbnailColumnToVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('videos')) {
            if (!Schema::hasColumn('videos', 'thumbnail')) {
                Schema::table('videos', function (Blueprint $table) {
                    $table->string('thumbnail', 255)->nullable(true)->after('link');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('videos')) {
            if (Schema::hasColumn('videos', 'thumbnail')) {
                Schema::table('videos', function (Blueprint $table) {
                    $table->dropColumn('thumbnail');
                });
            }
        }
    }
}

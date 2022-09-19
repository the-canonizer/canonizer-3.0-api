<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHomePageVideoUrlToVideoPostCastTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('videopodcast', function (Blueprint $table) {
            if (!Schema::hasColumn('videopodcast', 'home_page_video_url')) {
                $table->string('home_page_video_url')->default('https://player.vimeo.com/video/307590745');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('videopodcast', function (Blueprint $table) {
            if (Schema::hasColumn('videopodcast', 'home_page_video_url')) {
                $table->dropColumn('home_page_video_url');
            }
        });
    }
}

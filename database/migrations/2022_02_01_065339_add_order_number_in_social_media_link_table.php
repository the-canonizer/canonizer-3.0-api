<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderNumberInSocialMediaLinkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('social_links') && !Schema::hasColumn('social_links', 'order_number')) {
            Schema::table('social_links', function (Blueprint $table) {
                $table->integer('order_number')->default(0);
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
        if (Schema::hasTable('social_links') && Schema::hasColumn('social_links', 'order_number')) {
            Schema::table('social_links', function (Blueprint $table) {
                $table->dropColumn('order_number');
            });
        }
    }
}

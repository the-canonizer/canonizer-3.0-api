<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('topics_tags')) {
            Schema::table('topics_tags', function (Blueprint $table) {
                $table->unsignedInteger('topic_id')->nullable()->after('id');
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
        if (Schema::hasTable('topics_tags')) {
            Schema::table('topics_tags', function (Blueprint $table) {
                if (Schema::hasColumn('topics_tags', 'topic_id')) {
                    $table->dropColumn('topic_id');
                }
            });
        }
    }
};

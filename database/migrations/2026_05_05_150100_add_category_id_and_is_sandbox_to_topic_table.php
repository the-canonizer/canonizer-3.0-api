<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (Schema::hasTable('topic')) {
            Schema::table('topic', function (Blueprint $table) {
                if (!Schema::hasColumn('topic', 'category_id')) {
                    $table->unsignedBigInteger('category_id')->nullable()->after('namespace_id');
                    $table->index('category_id');
                }
                if (!Schema::hasColumn('topic', 'is_sandbox')) {
                    $table->tinyInteger('is_sandbox')->default(0)->after('is_one_level');
                }
            });
        }
    }

    public function down()
    {
        Schema::table('topic', function (Blueprint $table) {
            if (Schema::hasColumn('topic', 'category_id')) {
                $table->dropIndex(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('topic', 'is_sandbox')) {
                $table->dropColumn('is_sandbox');
            }
        });
    }
};

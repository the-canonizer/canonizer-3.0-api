<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('namespace') && !Schema::hasColumn('namespace', 'sort_order')) {
            Schema::table('namespace', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->comment('Shorting Key');
            });
            DB::statement("UPDATE namespace SET sort_order = id");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('namespace') && Schema::hasColumn('namespace', 'sort_order')) {
            Schema::table('namespace', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};

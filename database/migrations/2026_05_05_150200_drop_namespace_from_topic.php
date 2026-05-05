<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Safety net: if any topic still has NULL category_id (classifier missed it,
        // or new topics were created between Deploy 1 and Deploy 2 without going
        // through the classifier path), assign a fallback so the NOT NULL alter
        // doesn't fail. "Culture & Society" is the broadest preset.
        $fallbackId = DB::table('topic_categories')
            ->where('name', 'Culture & Society')
            ->value('id');
        if ($fallbackId) {
            DB::table('topic')
                ->whereNull('category_id')
                ->update(['category_id' => $fallbackId]);
        }

        DB::statement('ALTER TABLE topic MODIFY category_id BIGINT UNSIGNED NOT NULL');

        if (Schema::hasColumn('topic', 'namespace_id')) {
            Schema::table('topic', function (Blueprint $table) {
                $table->dropColumn('namespace_id');
            });
        }
        if (Schema::hasColumn('topic', 'namespace')) {
            Schema::table('topic', function (Blueprint $table) {
                $table->dropColumn('namespace');
            });
        }
    }

    public function down()
    {
        Schema::table('topic', function (Blueprint $table) {
            if (!Schema::hasColumn('topic', 'namespace_id')) {
                $table->integer('namespace_id')->nullable();
            }
            if (!Schema::hasColumn('topic', 'namespace')) {
                $table->string('namespace', 250)->nullable();
            }
        });
        DB::statement('ALTER TABLE topic MODIFY category_id BIGINT UNSIGNED NULL');
    }
};

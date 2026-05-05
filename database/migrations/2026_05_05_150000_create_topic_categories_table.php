<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('topic_categories')) {
            Schema::create('topic_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('description', 500)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('topic_categories');
    }
};

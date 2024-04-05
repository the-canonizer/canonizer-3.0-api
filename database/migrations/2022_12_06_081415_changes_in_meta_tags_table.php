<?php

use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangesInMetaTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('meta_tags');
        Schema::create('meta_tags', function (Blueprint $table) {
            $table->id();
            $table->string('page_name')->nullable();
            $table->boolean('is_static')->default(0);
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('submitter_nick_id')->nullable();
            $table->string('image_url')->nullable();
            $table->json('keywords')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meta_tags');
    }
}

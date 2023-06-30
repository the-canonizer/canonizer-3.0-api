<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmbeddedCodeTrackingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('embedded_code_tracking')) {
            Schema::create('embedded_code_tracking', function (Blueprint $table) {
                $table->id();
                $table->string('url', 500);
                $table->string('ip_address', 100)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->integer('created_at');
                $table->integer('updated_at');
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
        Schema::dropIfExists('embedded_code_tracking');
    }
}

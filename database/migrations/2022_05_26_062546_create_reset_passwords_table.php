<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResetPasswordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('reset_passwords')) {
            Schema::create('reset_passwords', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->dateTime('reset_at')->nullable();
                $table->dateTime('expires_at')->nullable();
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
        Schema::dropIfExists('reset_passwords');
    }
}

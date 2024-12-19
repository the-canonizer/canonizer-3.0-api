<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSocialDataDeletionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_data_deletion_requests', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('provider');
            $table->string('provider_id');
            $table->tinyInteger('status')->comment('0: Pending, 1: Completed')->default(0);
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
        Schema::dropIfExists('social_data_deletion_requests');
    }
}

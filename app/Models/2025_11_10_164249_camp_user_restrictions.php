<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CampUserRestrictions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('camp_user_restrictions')) {
            Schema::create('camp_user_restrictions', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('camp_id')->index();
                $table->integer('camp_num')->index();
                $table->integer('topic_num')->index();
                $table->integer('restricted_user_id')->index();
                $table->integer('restricted_by')->index(); // leader/admin
                $table->text('reason');
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->enum('status', ['active','lifted','expired'])->default('active');
                $table->timestamps();

                // foreign keys 
                $table->foreign('restricted_user_id')->references('id')->on('person')->cascadeOnDelete();
                $table->foreign('restricted_by')->references('id')->on('person')->cascadeOnDelete();
                $table->foreign('camp_id')->references('id')->on('camp')->cascadeOnDelete();
                $table->foreign('camp_num')->references('camp_num')->on('camp')->cascadeOnDelete();
                $table->foreign('topic_num')->references('topic_num')->on('camp')->cascadeOnDelete();
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
        Schema::dropIfExists('camp_user_restrictions');
    }
}

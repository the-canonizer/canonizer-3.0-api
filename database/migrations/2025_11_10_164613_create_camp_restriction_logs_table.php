<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampRestrictionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('camp_restriction_logs')) {
            Schema::create('camp_restriction_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('restriction_id')->index();
                $table->enum('action', ['restricted','lifted','extended','expired']);
                $table->unsignedInteger('performed_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('restriction_id')->references('id')->on('camp_user_restrictions')->cascadeOnDelete();
                $table->foreign('performed_by')->references('id')->on('person')->nullOnDelete();
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
        Schema::dropIfExists('camp_restriction_logs');
    }
}

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
                $table->unsignedBigInteger('camp_id')->index();
                $table->integer('camp_num')->index();
                $table->integer('topic_num')->index();
                $table->unsignedBigInteger('restricted_user_id')->index();
                $table->unsignedBigInteger('restricted_by')->index(); // leader/admin
                $table->text('reason');
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->enum('status', ['active','lifted','expired'])->default('active');
                $table->timestamps();

                // foreign keys
                // FKs disabled — fail to apply on production MySQL/InnoDB config
                // (charset/collation mismatch with referenced tables); table is
                // already in production without these FKs and the app code
                // does not depend on them.
                // $table->foreign('restricted_user_id')->references('id')->on('person')->cascadeOnDelete();
                // $table->foreign('restricted_by')->references('id')->on('person')->cascadeOnDelete();
                // $table->foreign('camp_id')->references('id')->on('camp')->cascadeOnDelete();
                // $table->foreign('camp_num')->references('camp_num')->on('camp')->cascadeOnDelete();
                // $table->foreign('topic_num')->references('topic_num')->on('camp')->cascadeOnDelete();
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
        if (Schema::hasTable('camp_user_restrictions')) {

            Schema::table('camp_user_restrictions', function (Blueprint $table) {
                // Drop FKs safely (only if they exist)
                // Drop FK only if table has it
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $doctrineTable = $sm->listTableDetails('camp_user_restrictions');

                if ($doctrineTable->hasForeignKey('camp_user_restrictions_restricted_user_id_foreign')) {
                    $table->dropForeign('camp_user_restrictions_restricted_user_id_foreign');
                }

                if ($doctrineTable->hasForeignKey('camp_user_restrictions_restricted_by_foreign')) {
                    $table->dropForeign('camp_user_restrictions_restricted_by_foreign');
                }

                if ($doctrineTable->hasForeignKey('camp_user_restrictions_camp_id_foreign')) {
                    $table->dropForeign('camp_user_restrictions_camp_id_foreign');
                }

                if ($doctrineTable->hasForeignKey('camp_user_restrictions_camp_num_foreign')) {
                    $table->dropForeign('camp_user_restrictions_camp_num_foreign');
                }

                if ($doctrineTable->hasForeignKey('camp_user_restrictions_topic_num_foreign')) {
                    $table->dropForeign('camp_user_restrictions_topic_num_foreign');
                }
            });

         Schema::dropIfExists('camp_user_restrictions');
        }
    }
}

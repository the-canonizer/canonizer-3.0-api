<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardens user_passkeys (created in 2026_10_13_000001):
 *  - user_id becomes BIGINT UNSIGNED, matching person.id, with a FK that cascades on delete
 *  - credential_id (up to 1023 raw bytes per the WebAuthn spec) becomes TEXT; uniqueness and
 *    lookups move to credential_id_hash, a SHA-256 hex digest, because TEXT cannot be indexed whole
 */
class HardenUserPasskeysTable extends Migration
{
    public function up()
    {
        Schema::table('user_passkeys', function (Blueprint $table) {
            $table->char('credential_id_hash', 64)->nullable()->after('credential_id');
        });
        DB::statement('UPDATE user_passkeys SET credential_id_hash = SHA2(credential_id, 256)');

        Schema::table('user_passkeys', function (Blueprint $table) {
            $table->dropUnique('user_passkeys_credential_id_unique');
        });
        DB::statement('ALTER TABLE user_passkeys MODIFY credential_id TEXT NOT NULL');
        DB::statement('ALTER TABLE user_passkeys MODIFY credential_id_hash CHAR(64) NOT NULL');
        DB::statement('ALTER TABLE user_passkeys MODIFY user_id BIGINT UNSIGNED NOT NULL');

        // Passkeys whose user no longer exists are unusable and would block the FK.
        DB::statement('DELETE FROM user_passkeys WHERE user_id NOT IN (SELECT id FROM person)');

        Schema::table('user_passkeys', function (Blueprint $table) {
            $table->unique('credential_id_hash');
            $table->foreign('user_id')->references('id')->on('person')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('user_passkeys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['credential_id_hash']);
        });
        DB::statement('ALTER TABLE user_passkeys MODIFY user_id INT NOT NULL');
        // Fails if a stored credential_id is longer than 512 characters.
        DB::statement('ALTER TABLE user_passkeys MODIFY credential_id VARCHAR(512) NOT NULL');
        Schema::table('user_passkeys', function (Blueprint $table) {
            $table->unique('credential_id');
            $table->dropColumn('credential_id_hash');
        });
    }
}

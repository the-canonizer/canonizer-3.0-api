<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateUserSupportSp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create stored procedure
        DB::unprepared('CREATE PROCEDURE `user_support`(
                IN support_type VARCHAR(10), 
                IN var_user_id INT, 
                IN page_offset INT,
                IN page_limit INT,
                IN search_topic_name VARCHAR(255)
            )
            BEGIN
                DECLARE start_index INT;
                DECLARE total_records INT;

                SET start_index = page_offset * page_limit;  -- Adjust start index for pagination

                -- Temporary table for filtered topics
                CREATE TEMPORARY TABLE IF NOT EXISTS temp_filtered_topics AS
                    SELECT
                        a.topic_num,
                        a.namespace_id,
                        a.topic_name
                    FROM
                        topic a
                    INNER JOIN
                        (SELECT topic_num, MAX(go_live_time) AS live_time
                         FROM topic
                         WHERE objector_nick_id IS NULL
                         AND go_live_time <= UNIX_TIMESTAMP(NOW())
                         GROUP BY topic_num) b
                    ON a.topic_num = b.topic_num
                    AND a.go_live_time = b.live_time
                    WHERE (search_topic_name IS NULL OR a.topic_name LIKE CONCAT("%", search_topic_name, "%"));

                -- Temporary table for filtered camps
                CREATE TEMPORARY TABLE IF NOT EXISTS temp_filtered_camps AS
                    SELECT
                        a.topic_num,
                        a.camp_num,
                        a.camp_name
                    FROM
                        camp a
                    INNER JOIN
                        (SELECT topic_num, camp_num, MAX(go_live_time) AS live_time
                        FROM camp
                        WHERE objector_nick_id IS NULL
                        AND go_live_time <= UNIX_TIMESTAMP(NOW())
                        AND grace_period = 0
                        GROUP BY topic_num, camp_num) b
                    ON a.topic_num = b.topic_num
                    AND a.camp_num = b.camp_num
                    AND a.go_live_time = b.live_time;

                IF (support_type = "direct") THEN
                 
                    SELECT COUNT(*) INTO total_records
                    FROM temp_filtered_topics a
                    JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
                    JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
                    WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
                    AND c.delegate_nick_name_id = 0
                    AND c.end = 0;

                    -- Return paginated data for direct support
                    SELECT
                        a.topic_num,
                        b.camp_num,
                        c.support_order,
                        a.topic_name AS title,
                        b.camp_name,
                        c.start,
                        c.support_id,
                        a.namespace_id,
                        c.nick_name_id
                    FROM temp_filtered_topics a
                    JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
                    JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
                    WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
                    AND c.delegate_nick_name_id = 0
                    AND c.end = 0
                    ORDER BY a.topic_num DESC
                    LIMIT start_index, page_limit;

                -- Handle delegate support type
                ELSEIF (support_type = "delegate") THEN
                    -- Calculate total records for "delegate" support
                    SELECT COUNT(*) INTO total_records
                    FROM temp_filtered_topics a
                    JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
                    JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
                    JOIN nick_name d ON c.nick_name_id = d.id
                    JOIN nick_name e ON c.delegate_nick_name_id = e.id
                    WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
                    AND c.delegate_nick_name_id != 0
                    AND c.end = 0;

                    -- Return paginated data for delegate support
                    SELECT
                        a.topic_num,
                        b.camp_num,
                        c.support_order,
                        a.topic_name AS title,
                        b.camp_name,
                        d.nick_name AS my_nick_name,
                        e.nick_name AS delegated_to_nick_name,
                        c.start,
                        c.support_id,
                        a.namespace_id,
                        c.nick_name_id,
                        c.delegate_nick_name_id,
                        e.user_id AS delegate_user_id
                    FROM temp_filtered_topics a
                    JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
                    JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
                    JOIN nick_name d ON c.nick_name_id = d.id
                    JOIN nick_name e ON c.delegate_nick_name_id = e.id
                    WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
                    AND c.delegate_nick_name_id != 0
                    AND c.end = 0
                    ORDER BY a.topic_num DESC
                    LIMIT start_index, page_limit;

                END IF;

                -- Return total record count
                SELECT total_records AS total_records;

                -- Drop temporary tables after usage
                DROP TEMPORARY TABLE IF EXISTS temp_filtered_topics;
                DROP TEMPORARY TABLE IF EXISTS temp_filtered_camps;

            END;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the stored procedure if it exists
        DB::unprepared('DROP PROCEDURE IF EXISTS user_support');
    }
}

DROP PROCEDURE IF EXISTS `user_support`;

CREATE PROCEDURE `user_support`(
                IN support_type VARCHAR(10), 
                IN var_user_id INT, 
                IN page_offset INT,
                IN page_limit INT,
                IN search_topic_name VARCHAR(255)
            )
BEGIN
    DECLARE start_index INT;
    DECLARE total_records INT;

    -- Adjust start index for pagination
    SET start_index = page_offset * page_limit;
    SET SESSION group_concat_max_len = 1000000;  

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
        
-- Return paginated data for direct support
    IF (support_type = "direct") THEN
        -- Get total records for direct support
       SELECT COUNT(*) INTO total_records
        FROM (
            SELECT
                a.topic_num
            FROM
                temp_filtered_topics a
            JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
            JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
            WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
            AND c.delegate_nick_name_id = 0
            AND c.end = 0
            AND (search_topic_name IS NULL OR a.topic_name LIKE CONCAT("%", search_topic_name, "%"))
            GROUP BY a.topic_num
        ) AS count_query;
	
		SELECT
            a.topic_num,
            group_concat(distinct c.nick_name_id) AS nick_name_id,
            group_concat(distinct a.topic_name) AS title,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'title', a.topic_name,
                    'topic_num', a.topic_num,
                    'support_order', c.support_order,
                    'camp_name', b.camp_name,
                    'camp_num', b.camp_num,
                    'namespace_id', a.namespace_id,
                    'nick_name_id', c.nick_name_id,
                    'support_id', c.support_id
                ) ORDER BY c.support_order ASC
            ) AS details
        FROM
            temp_filtered_topics a
        JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
        JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
        WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
        AND c.delegate_nick_name_id = 0
        AND c.end = 0
        AND (search_topic_name IS NULL OR a.topic_name LIKE CONCAT("%", search_topic_name, "%"))
        GROUP BY a.topic_num
        ORDER BY a.topic_num DESC
        LIMIT start_index, page_limit;
            
    ELSEIF (support_type = "delegate") THEN
        -- Calculate total records for "delegate" support
      SELECT COUNT(*) INTO total_records
FROM (
	SELECT
              a.topic_num
        FROM 
        	temp_filtered_topics a
        JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
        JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
        JOIN nick_name d ON c.nick_name_id = d.id
        JOIN nick_name e ON c.delegate_nick_name_id = e.id
        WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
        AND c.delegate_nick_name_id != 0
        AND c.end = 0
        AND (search_topic_name IS NULL OR a.topic_name LIKE CONCAT("%", search_topic_name, "%"))
            GROUP BY a.topic_num
        ) AS count_query;

        -- Return paginated data for delegate support
        SELECT
           a.topic_num,
             GROUP_CONCAT(
                JSON_OBJECT(
					'title', a.topic_name,
                    'topic_num', a.topic_num,
                    'camp_num', b.camp_num,
                    'support_order', c.support_order,
                    'camp_name', b.camp_name,
                    'start',c.start,
                    'suport_id', c.support_id
                ) ORDER BY c.support_order ASC
            ) AS details,
			 group_concat(distinct b.camp_num) as camp_num,
             group_concat(distinct a.topic_name) as title,
             group_concat(distinct d.nick_name) as my_nick_name,
			 group_concat(distinct e.nick_name) as delegated_to_nick_name,
			 group_concat(distinct c.delegate_nick_name_id) as delegate_nick_name_id,
			 group_concat(distinct e.user_id) as delegate_user_id,
			 group_concat(distinct a.namespace_id) as namespace_id, 
			 group_concat(distinct c.nick_name_id) as nick_name_id
        FROM temp_filtered_topics a
        JOIN temp_filtered_camps b ON a.topic_num = b.topic_num
        JOIN support c ON a.topic_num = c.topic_num AND b.camp_num = c.camp_num
        JOIN nick_name d ON c.nick_name_id = d.id
        JOIN nick_name e ON c.delegate_nick_name_id = e.id
        WHERE c.nick_name_id IN (SELECT id FROM nick_name WHERE user_id = var_user_id)
        AND c.delegate_nick_name_id != 0
        AND c.end = 0  
        group by a.topic_num
        ORDER BY a.topic_num DESC
        LIMIT start_index, page_limit;
    END IF;

    -- Return total record count
    SELECT total_records AS total_records;

    -- Drop temporary tables after usage
    DROP TEMPORARY TABLE IF EXISTS temp_filtered_topics;
    DROP TEMPORARY TABLE IF EXISTS temp_filtered_camps;

END
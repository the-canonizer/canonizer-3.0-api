DROP PROCEDURE IF EXISTS `user_support`;

CREATE PROCEDURE `user_support`(IN `support_type` VARCHAR(10), IN `var_user_id` INT)
    
    BEGIN
        IF (support_type = 'direct')  
        THEN
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
        FROM
            (SELECT
            a.namespace,
            a.topic_num,
            a.topic_name,
            a.namespace_id
            FROM
            topic a
            INNER JOIN
                (SELECT
                topic_num,
                MAX(go_live_time) AS live_time
                FROM
                topic
                WHERE objector_nick_id IS NULL
                AND go_live_time <= UNIX_TIMESTAMP (NOW())
                GROUP BY topic_num) b
                ON a.topic_num = b.topic_num
                AND a.go_live_time = b.live_time) a
            JOIN
            (SELECT
                a.topic_num,
                a.camp_num,
                a.camp_name
            FROM
                camp a
                INNER JOIN
                (SELECT
                    topic_num,
                    camp_num,
                    MAX(`go_live_time`) AS live_time
                FROM
                    camp
                WHERE objector_nick_id IS NULL
                    AND go_live_time <= UNIX_TIMESTAMP (NOW())
                    AND grace_period = 0
                GROUP BY topic_num,
                    camp_num) b
                ON a.topic_num = b.topic_num
                AND a.camp_num = b.camp_num
                AND a.go_live_time = b.live_time) b,
            support c
        WHERE a.topic_num = b.topic_num
        AND a.topic_num = c.topic_num
        AND b.camp_num = c.camp_num
            AND c.nick_name_id IN
            (SELECT
            id
            FROM
            nick_name
            WHERE user_id = var_user_id)
            AND c.delegate_nick_name_id = 0
            AND c.end = 0
        ORDER BY c.support_order ASC,
            c.start DESC,
            a.topic_num;
            
        ELSEIF (support_type = 'delegate') 
        THEN
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
        FROM
            (SELECT
            a.namespace,
            a.topic_num,
            a.topic_name,
            a.namespace_id
            FROM
            topic a
            INNER JOIN
                (SELECT
                topic_num,
                MAX(go_live_time) AS live_time
                FROM
                topic
                WHERE objector_nick_id IS NULL
                AND go_live_time <= UNIX_TIMESTAMP (NOW())
                GROUP BY topic_num) b
                ON a.topic_num = b.topic_num
                AND a.go_live_time = b.live_time) a
            JOIN
            (SELECT
                a.topic_num,
                a.camp_num,
                a.camp_name
            FROM
                camp a
                INNER JOIN
                (SELECT
                    topic_num,
                    camp_num,
                    MAX(`go_live_time`) AS live_time
                FROM
                    camp
                WHERE objector_nick_id IS NULL
                    AND go_live_time <= UNIX_TIMESTAMP (NOW())
                    AND grace_period = 0
                GROUP BY topic_num,
                    camp_num) b
                ON a.topic_num = b.topic_num
                AND a.camp_num = b.camp_num
                AND a.go_live_time = b.live_time) b,
            support c,
            nick_name d,
            nick_name e
        WHERE a.topic_num = b.topic_num
        AND a.topic_num = c.topic_num
        AND b.camp_num = c.camp_num
        AND c.`nick_name_id` = d.id
        AND c.`delegate_nick_name_id` = e.id
            AND c.nick_name_id IN
            (SELECT
            id
            FROM
            nick_name
            WHERE user_id = var_user_id)
            AND c.delegate_nick_name_id != 0
            AND c.end = 0
        ORDER BY c.support_order ASC,
            c.start DESC,
            a.topic_num;
        END IF;
    END 
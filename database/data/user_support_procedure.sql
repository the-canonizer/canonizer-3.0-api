
DROP PROCEDURE IF EXISTS `user_support`;

CREATE PROCEDURE `user_support`(IN `support_type` VARCHAR(10), IN `user_id` INT)
    BEGIN

        IF (support_type = 'direct') # QUERY TO GET USER'S DIRECT SUPPORT TOPICS AND CAMPS
        THEN 
        SELECT 
            t2.topic_num,
            t2.camp_num,
            t2.support_order,
            t1.title,
            t2.camp_name,
            t2.start,
            t2.support_id,
            t3.namespace_id,
            t2.nick_name_id
        FROM
            (SELECT 
            a.topic_num,
            a.title 
            FROM
            camp a 
            INNER JOIN 
                (SELECT 
                topic_num,
                MAX(go_live_time) AS live_time 
                FROM
                camp 
                WHERE objector_nick_id IS NULL 
                AND camp_num = 1 
                AND go_live_time <= UNIX_TIMESTAMP(NOW()) 
                GROUP BY topic_num) b 
                ON a.topic_num = b.topic_num 
                AND a.go_live_time = b.live_time 
                GROUP BY  a.topic_num, a.title) t1,
            (SELECT 
            b.topic_num,
            b.camp_num,
            c.support_order,
            b.title AS topic_name,
            b.camp_name,
            c.start,
            c.support_id,
            c.nick_name_id
            FROM
            (SELECT 
                a.* 
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
                    AND go_live_time <= UNIX_TIMESTAMP(NOW()) 
                GROUP BY topic_num,
                    camp_num) b 
                ON a.topic_num = b.topic_num 
                AND a.camp_num = b.camp_num 
                AND a.go_live_time = b.live_time) b,
            support c 
            WHERE b.camp_num = c.camp_num 
            AND b.topic_num = c.topic_num 
            AND c.nick_name_id IN 
            (SELECT 
                id 
            FROM
                nick_name 
            WHERE owner_code = 
                (SELECT 
                TO_BASE64 (CONCAT('Malia', user_id, 'Malia')))) 
            AND c.delegate_nick_name_id = 0 
            AND c.end = 0) t2,
        (SELECT 
            topic_num,
            namespace_id 
        FROM
            topic 
        GROUP BY topic_num,
            namespace_id) t3 
        WHERE t1.topic_num = t2.topic_num 
        AND t1.topic_num = t3.topic_num 
        ORDER BY t2.support_order ASC,t2.start DESC, t2.topic_num;
            
        ELSEIF (support_type = 'delegate') #QUERY TO GET USER'S DELEGATE SUPPORT TOPICS AND CAMPS
        THEN 
        SELECT 
        t2.topic_num,
        t2.camp_num,
        t2.support_order,
        t1.title,
        t2.camp_name,
        t2.my_nick_name,
        t2.delegated_to_nick_name,
        t2.start,
        t2.support_id,
        t3.namespace_id,
        t2.nick_name_id,
        delegate_nick_name_id,
        delegate_user_id
        FROM
        (SELECT 
            a.topic_num,
            a.title 
        FROM
            camp a 
            INNER JOIN 
            (SELECT 
                topic_num,
                MAX(go_live_time) AS live_time 
            FROM
                camp 
            WHERE objector_nick_id IS NULL 
                AND camp_num = 1 
                AND go_live_time <= UNIX_TIMESTAMP(NOW()) 
            GROUP BY topic_num) b 
            ON a.topic_num = b.topic_num 
            AND a.go_live_time = b.live_time
            GROUP BY  a.topic_num, a.title) t1,
        (SELECT 
            b.topic_num,
            b.camp_num,
            c.support_order,
            b.title AS topic_name,
            b.camp_name,
            d.nick_name AS my_nick_name,
            e.nick_name AS delegated_to_nick_name,
            c.start,
            c.support_id,
            c.delegate_nick_name_id,
            c.nick_name_id,
            REPLACE(from_base64(e.owner_code),'Malia','') AS delegate_user_id
        FROM
            (SELECT 
            a.* 
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
                AND go_live_time <= UNIX_TIMESTAMP(NOW()) 
                GROUP BY topic_num,
                camp_num) b 
                ON a.topic_num = b.topic_num 
                AND a.camp_num = b.camp_num 
                AND a.go_live_time = b.live_time) b,
            support c,
            nick_name d,
            nick_name e 
        WHERE b.camp_num = c.camp_num 
            AND b.topic_num = c.topic_num 
            AND c.`nick_name_id` = d.id 
            AND c.`delegate_nick_name_id` = e.id 
            AND c.nick_name_id IN 
            (SELECT 
            id 
            FROM
            nick_name 
            WHERE owner_code = 
            (SELECT 
                TO_BASE64 (CONCAT('Malia', user_id, 'Malia')))) 
            AND c.delegate_nick_name_id != 0 
            AND c.end = 0) t2,
        (SELECT 
            topic_num,
            namespace_id 
        FROM
            topic 
        GROUP BY topic_num,
            namespace_id) t3 
        WHERE t1.topic_num = t2.topic_num 
        AND t1.topic_num = t3.topic_num 
        ORDER BY t2.support_order ASC, t2.start DESC, t2.topic_num;
        END IF ;
    END;
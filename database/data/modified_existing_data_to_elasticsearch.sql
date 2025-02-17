        DROP PROCEDURE IF EXISTS `sp_sync_data_to_elasticsearch`;

        CREATE PROCEDURE `sp_sync_data_to_elasticsearch`()
        BEGIN
        -- DROP TABLE IF EXISTS
        DROP TABLE IF EXISTS elasticsearch_data;
        
        -- CREATE TABLE to store data for elasticsearch
        
        CREATE TABLE elasticsearch_data (
            id VARCHAR(255),
            record_id INT,
            is_live BOOLEAN,
			is_archive BOOLEAN,
            type_value LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
            topic_num INT,
            camp_num INT,
            go_live_time VARCHAR(255),
            statement_num VARCHAR(255),
            nick_name_id BIGINT,
            namespace VARCHAR(255),
            link VARCHAR(255),
            breadcrumb_data JSON,
            support_count VARCHAR(255),
            `type` VARCHAR(255)
        );

        -- Live Topic Records
	    INSERT INTO
            elasticsearch_data
        SELECT
            CONCAT('topic-',a.topic_num,"-live") AS id,
            a.id AS record_id,
            '1' AS is_live,
			'0' AS is_archive,
            '' AS type_value,
            a.topic_num,
            '1' AS camp_num,
            a.go_live_time,
            '' AS statement_num,
            0 AS nick_name_id,
            REPLACE(TRIM(BOTH '/' FROM c.label), '/', ' > ') AS namespace,
            CONCAT('topic/',a.topic_num,'-',REPLACE(REPLACE(REPLACE(a.topic_name,'https://','https---'),'.','-'),'/','-'),'/1-Agreement') AS link,
            JSON_OBJECT() AS breadcrumb_data,
            '' AS support_count,
            'topic' AS `type`
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
            GROUP BY topic_num
            ) b
        ON a.topic_num = b.topic_num
        AND a.go_live_time = b.live_time
        LEFT JOIN namespace c
        ON a.namespace_id = c.id;
        
        -- REVIEW TOPIC RECORDS
        
        INSERT INTO
            elasticsearch_data
        SELECT
            CONCAT('topic-',a.topic_num,"-review") AS id,
            a.id AS record_id,
            '0' AS is_live,
            '0' AS is_archive,
            '' AS type_value,
            a.topic_num,
            '1' AS camp_num,
            a.go_live_time,
            '' AS statement_num,
            0 AS nick_name_id,
            REPLACE(TRIM(BOTH '/' FROM c.label), '/', ' > ') AS namespace,
            CONCAT('topic/',a.topic_num,'-',REPLACE(REPLACE(REPLACE(a.topic_name,'https://','https---'),'.','-'),'/','-'),'/1-Agreement') AS link,
            JSON_OBJECT() AS breadcrumb_data,
            '' AS support_count,
            'topic' AS `type`
        FROM
            topic a
        INNER JOIN
            (SELECT
            topic_num,
            MAX(go_live_time) AS live_time
            FROM
            topic
            WHERE objector_nick_id IS NULL AND grace_period = 0
            GROUP BY topic_num
            ) b
        ON a.topic_num = b.topic_num
        AND a.go_live_time = b.live_time
        LEFT JOIN namespace c
        ON a.namespace_id = c.id;
        
        -- INSERT NICKNAME DATA IN  TABLE
        INSERT INTO
        elasticsearch_data
        SELECT 
            CONCAT('nickname-',a.id) AS id,
            a.id AS record_id,
            '1' AS is_live,
			'0' AS is_archive,
            nick_name AS type_value,
            0 AS topic_num,
            0 AS camp_num,
            0 AS go_live_time,
            '' AS statement_num,
            a.id AS nick_name_id,
            '' AS namespace,
            CONCAT('/user/supports/',a.id,'?topicnum=&campnum=&canon=1') AS link,
            JSON_OBJECT() AS breadcrumb_data,
            IFNULL(b.support_count,0) AS support_count,
            'nickname' AS `type`
        FROM nick_name a
        LEFT JOIN
            (SELECT nick_name_id , COUNT(1) AS support_count
            FROM support
            WHERE `end` = 0
            GROUP BY nick_name_id) b
        ON a.id = b.nick_name_id
        WHERE private = 0;       
        
        
        -- INSERT LIVE CAMP DATA IN TABLE
        
        INSERT INTO
        elasticsearch_data
        SELECT
            CONCAT('camp-',a.topic_num,'-',a.camp_num,'-live') AS id,
            a.id AS record_id,
            '1' AS is_live,
            a.is_archive AS is_archive,
            a.camp_name AS type_value,
            a.topic_num,
            a.camp_num,
            a.go_live_time,
            '' AS statement_num,
            0 AS nick_name_id,
            '' AS namespace,
            '' AS link,
            JSON_OBJECT() AS breadcrumb_data,
            '' AS support_count,
            'camp' AS `type`
        FROM
            camp a
        INNER JOIN
            (SELECT
                topic_num,
                camp_num,
                MAX(go_live_time) AS live_time
            FROM
                camp
            WHERE objector_nick_id IS NULL
                AND go_live_time <= UNIX_TIMESTAMP(NOW())
                AND grace_period = 0
            GROUP BY topic_num, camp_num
            ORDER BY topic_num, camp_num
            ) b
        ON a.topic_num = b.topic_num
        AND a.camp_num = b.camp_num
        AND a.go_live_time = b.live_time;

        -- INSERT REVIEW CAMP DATA IN TABLE
        
        INSERT INTO
        elasticsearch_data
        SELECT
            CONCAT('camp-',a.topic_num,'-',a.camp_num,'-review') AS id,
            a.id AS record_id,
            '0' AS is_live,
            a.is_archive AS is_archive,
            '' AS type_value,
            a.topic_num,
            a.camp_num,
            a.go_live_time,
            '' AS statement_num,
            0 AS nick_name_id,
            '' AS namespace,
            '' AS link,
            JSON_OBJECT() AS breadcrumb_data,
            '' AS support_count,
            'camp' AS `type`
        FROM
            camp a
        INNER JOIN
            (SELECT
                topic_num,
                camp_num,
                MAX(go_live_time) AS live_time
            FROM
                camp
            WHERE objector_nick_id IS NULL
                AND grace_period = 0
            GROUP BY topic_num, camp_num
            ORDER BY topic_num, camp_num
            ) b
        ON a.topic_num = b.topic_num
        AND a.camp_num = b.camp_num
        AND a.go_live_time = b.live_time;
            
        -- INSERT LIVE STATEMENT DATA IN  TABLE
        INSERT INTO
        elasticsearch_data
        SELECT 
            CONCAT('statement-',a.topic_num,'-',a.camp_num,"-live") AS id,
            a.id AS record_id,
            '1' AS is_live,
            '0' AS is_archive,
            '' AS type_value, 
            a.topic_num,
            a.camp_num,
            a.go_live_time,
            a.id AS statement_num,
            0 AS nick_name_id,
            '' AS namespace,
            '' AS link,
            JSON_OBJECT() AS breadcrumb_data,
            '' AS support_count,
            'statement' AS `type`
        FROM 
            statement a
        INNER JOIN
            (
            SELECT
                topic_num,
                camp_num,
                MAX(`go_live_time`) AS live_time
            FROM
                statement
            WHERE objector_nick_id IS NULL
                AND go_live_time <= UNIX_TIMESTAMP (NOW())
                AND grace_period = 0
            GROUP BY topic_num,
                camp_num
            ) b
        ON a.topic_num = b.topic_num
        AND a.camp_num = b.camp_num
        AND a.go_live_time = b.live_time;
        
		-- INSERT REVIEW STATEMENT DATA IN  TABLE
        INSERT INTO
        elasticsearch_data
        SELECT 
            CONCAT('statement-',a.topic_num,'-',a.camp_num,"-review") AS id,
            a.id AS record_id,
            '0' AS is_live,
            '0' AS is_archive,
            '' AS type_value, 
            a.topic_num,
            a.camp_num,
            a.go_live_time,
            a.id AS statement_num,
            0 AS nick_name_id,
            '' AS namespace,
            '' AS link,
            JSON_OBJECT() AS breadcrumb_data,
            '' AS support_count,
            'statement' AS `type`
        FROM 
            statement a
        INNER JOIN
            (
            SELECT
                topic_num,
                camp_num,
                MAX(`go_live_time`) AS live_time
            FROM
                statement
            WHERE objector_nick_id IS NULL
                AND grace_period = 0
            GROUP BY topic_num,
                camp_num
            ) b
        ON a.topic_num = b.topic_num
        AND a.camp_num = b.camp_num
        AND a.go_live_time = b.live_time;
    
END;
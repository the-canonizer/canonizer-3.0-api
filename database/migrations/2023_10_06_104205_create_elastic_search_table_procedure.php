<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateElasticSearchTableProcedure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "DROP PROCEDURE IF EXISTS `sp_sync_data_to_elasticsearch`;
                        CREATE  PROCEDURE `sp_sync_data_to_elasticsearch`()
                        BEGIN
                            DECLARE var_topic_num INT;
                            DECLARE var_camp_num INT;
                            DECLARE var_live_time INT;
                            DECLARE var_parent_hierarchy JSON;
                            DECLARE var_statement_topic_num INT;
                            DECLARE var_statement_camp_num INT;
                            DECLARE var_statement_live_time INT;
                            DECLARE var_statement_parent_hierarchy JSON;
                            DECLARE done INT DEFAULT FALSE ;
                            
                            -- CURSOR DECLARATION FOR CAMPS
                            
                            DECLARE camp_breadcrumb_curs CURSOR FOR
                            SELECT
                                topic_num,
                                camp_num,
                                MAX(go_live_time) AS live_time
                            FROM
                                camp
                            WHERE 
                                objector_nick_id IS NULL
                                AND go_live_time <= UNIX_TIMESTAMP(NOW())
                                AND grace_period = 0
                            GROUP BY topic_num, camp_num
                            ORDER BY topic_num, camp_num;
                            
                            -- CURSOR DECLARATION FOR STATEMENTS
                            
                            DECLARE statement_breadcrumb_curs CURSOR FOR
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
                                camp_num;
                            
                            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE ;
                            
                            SET GLOBAL interactive_timeout=400;
                            
                            -- DROP TABLE IF EXISTS
                            DROP TABLE IF EXISTS elasticsearch_data;
                            
                            -- CREATE TABLE to store data for elasticsearch
                            
                            CREATE TABLE elasticsearch_data (
                                id VARCHAR(255),
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
                            
                            -- INSERT CAMP DATA IN TABLE
                            
                            INSERT INTO
                            elasticsearch_data
                            SELECT
                                CONCAT('camp-',a.topic_num,'-',a.camp_num,'-',a.id) AS id,
                                a.camp_name AS type_name,
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
                            -- LOOP TO UPDATE CAMP BREADCRUMB DATA IN  TABLE
                            
                            OPEN camp_breadcrumb_curs ;
                            read_loop :
                            LOOP
                                FETCH camp_breadcrumb_curs INTO 
                                var_topic_num,
                                var_camp_num,
                                var_live_time;
                            
                                -- RESET var_parent_hierarchy
                                SET var_parent_hierarchy = JSON_OBJECT();
                                -- RECURSIVE SQL CTE TO FETCH CAMP'S BREADCRUMB DATA IN JSON
                                
                            WITH RECURSIVE ParentHierarchy AS (
                                    SELECT
                                    h.camp_num,
                                    h.parent_camp_num,
                                    h.go_live_time,
                                    h.topic_num,
                                    1 AS h_level
                                    FROM camp h
                                    WHERE (h.topic_num, h.camp_num, h.go_live_time) IN (
                                    SELECT topic_num, camp_num, MAX(go_live_time) AS latest_timestamp
                                    FROM camp
                                    WHERE topic_num = var_topic_num
                                        AND camp_num = var_camp_num
                                        AND objector_nick_id IS NULL
                                        AND go_live_time <= UNIX_TIMESTAMP(NOW())
                                        AND grace_period = 0
                                    GROUP BY topic_num, camp_num
                                    )
                                    UNION ALL
                                    SELECT
                                    h.camp_num,
                                    h.parent_camp_num,
                                    h.go_live_time,
                                    h.topic_num,
                                    ph.h_level + 1
                                    FROM camp h
                                    INNER JOIN ParentHierarchy ph ON
                                    h.camp_num = ph.parent_camp_num
                                    AND h.topic_num = ph.topic_num
                                    WHERE (h.topic_num, h.camp_num, h.go_live_time) IN (
                                    SELECT topic_num, camp_num, MAX(go_live_time) AS latest_timestamp
                                    FROM camp
                                    WHERE topic_num = var_topic_num
                                        AND objector_nick_id IS NULL
                                        AND go_live_time <= UNIX_TIMESTAMP(NOW())
                                        AND grace_period = 0
                                    GROUP BY topic_num, camp_num
                                    )
                                LIMIT 200
                                )
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                    d.h_level, JSON_OBJECT(
                                        'topic_num', d.topic_num,
                                        'topic_name', t.topic_name,
                                        'camp_num', d.camp_num,
                                        'camp_name', e.camp_name,
                                        'camp_link', CONCAT('topic/',d.topic_num,'-',REPLACE(t.topic_name,' ','-'),'/',d.camp_num,'-',REPLACE(e.camp_name,' ','-')),
                                        'go_live_time', e.go_live_time
                                    )
                                    )
                                ) AS json_response INTO var_parent_hierarchy
                                FROM ParentHierarchy d INNER JOIN camp e
                                ON d.camp_num = e.camp_num
                                AND d.go_live_time = e.go_live_time
                                INNER JOIN 
                                (
                                    SELECT 
                                    a.topic_name,
                                    a.topic_num
                                    FROM
                                    topic a
                                    JOIN
                                    (
                                        SELECT
                                            topic_num,
                                            MAX(go_live_time) AS topic_live_time
                                        FROM
                                            topic
                                        WHERE
                                            topic_num = var_topic_num
                                            AND objector_nick_id IS NULL
                                            AND go_live_time <= UNIX_TIMESTAMP (NOW())
                                            
                                        GROUP BY 
                                            topic_num
                                    ) b
                                    ON a.topic_num = b.topic_num
                                    AND a.go_live_time = b.topic_live_time
                                )t
                                ON d.topic_num = t.topic_num
                                WHERE d.topic_num = var_topic_num
                                ORDER BY h_level;
                                
                                        
                                -- UPDATE CAMP RECORDS
                                
                                SET SQL_SAFE_UPDATES = 0;
                        
                                UPDATE elasticsearch_data
                                SET breadcrumb_data = var_parent_hierarchy
                                WHERE topic_num = var_topic_num 
                                AND camp_num = var_camp_num 
                                AND go_live_time = var_live_time
                                AND `type` = 'camp';
                                
                                
                                IF done THEN
                                    CLOSE camp_breadcrumb_curs ;
                                    SET done = FALSE; 
                                    LEAVE read_loop ;
                                END IF ;
                            END LOOP read_loop ;
                                
                            -- INSERT TOPIC DATA IN  TABLE
                                
                            INSERT INTO
                                elasticsearch_data
                            SELECT
                                CONCAT('topic-',a.topic_num,'-',a.id,'-',REPLACE(TRIM(BOTH '/' FROM IFNULL(c.label,'no-namespace')), '/', ' > ')) AS id,
                                a.topic_name AS type_name,
                                a.topic_num,
                                '1' AS camp_num,
                                a.go_live_time,
                                '' AS statement_num,
                                0 AS nick_name_id,
                                REPLACE(TRIM(BOTH '/' FROM c.label), '/', ' > ') AS namespace,
                                CONCAT('topic/',a.topic_num,'-',REPLACE(a.topic_name,' ','-'),'/1-Agreement') AS link,
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
                            ON a.namespace_id = c.parent_id;
                            
                            -- INSERT STATEMENT DATA IN  TABLE
                            INSERT INTO
                            elasticsearch_data
                            SELECT 
                                CONCAT('statement-',a.topic_num,'-',a.camp_num,'-',a.id) AS id,
                                a.VALUE AS type_name, 
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
                                
                            -- LOOP TO UPDATE STATEMENT BREADCRUMB DATA IN  TABLE
                            
                            OPEN statement_breadcrumb_curs;
                            read_loop :
                            LOOP
                                FETCH statement_breadcrumb_curs INTO 
                                var_statement_topic_num,
                                var_statement_camp_num,
                                var_statement_live_time;
                                
                            -- RECURSIVE SQL CTE TO FETCH STATEMENT'S BREADCRUMB DATA
                                
                                WITH RECURSIVE ParentHierarchy AS (
                                    SELECT
                                    h.camp_num,
                                    h.parent_camp_num,
                                    i.statement_live_time,
                                    i.camp_live_time,
                                    h.topic_num,
                                    1 AS h_level
                                FROM camp h
                                INNER JOIN
                                (
                                    SELECT a.topic_num, a.camp_num, MAX(a.go_live_time) camp_live_time,b.statement_live_time
                                    FROM camp a 
                                    INNER JOIN
                                    (
                                        SELECT s.topic_num, s.camp_num, MAX(s.go_live_time) AS statement_live_time
                                        FROM statement s 
                                        WHERE s.topic_num = var_statement_topic_num
                                            AND s.camp_num = var_statement_camp_num
                                            AND s.objector_nick_id IS NULL
                                            AND s.go_live_time <= UNIX_TIMESTAMP(NOW())
                                            AND s.grace_period = 0
                                        GROUP BY s.topic_num, s.camp_num
                                    ) b
                                    ON a.topic_num = b.topic_num AND a.camp_num = b.camp_num
                                    WHERE a.topic_num = var_statement_topic_num
                                        AND a.camp_num = var_statement_camp_num
                                        AND a.objector_nick_id IS NULL
                                        AND a.go_live_time <= UNIX_TIMESTAMP(NOW())
                                        AND a.grace_period = 0
                                    GROUP BY a.topic_num, a.camp_num
                                ) i
                                ON h.topic_num = i.topic_num 
                                AND h.camp_num = i.camp_num 
                                AND h.go_live_time = i.camp_live_time
                                UNION ALL
                                SELECT
                                    h.camp_num,
                                    h.parent_camp_num,
                                    i.statement_live_time,
                                    i.camp_live_time,
                                    h.topic_num,
                                    ph.h_level + 1
                                FROM 
                                    camp h
                                INNER JOIN 
                                    ParentHierarchy ph 
                                ON
                                    h.camp_num = ph.parent_camp_num
                                    AND h.topic_num = ph.topic_num
                                INNER JOIN
                                (
                                    SELECT a.topic_num, a.camp_num, MAX(a.go_live_time) camp_live_time,b.statement_live_time
                                    FROM camp a 
                                    INNER JOIN
                                    (
                                        SELECT s.topic_num, s.camp_num, MAX(s.go_live_time) AS statement_live_time
                                        FROM statement s 
                                        WHERE s.topic_num = var_statement_topic_num
                                            AND s.objector_nick_id IS NULL
                                            AND s.go_live_time <= UNIX_TIMESTAMP(NOW())
                                            AND s.grace_period = 0
                                        GROUP BY s.topic_num, s.camp_num
                                    ) b
                                    ON a.topic_num = b.topic_num AND a.camp_num = b.camp_num
                                    WHERE a.topic_num = var_statement_topic_num
                                        AND a.objector_nick_id IS NULL
                                        AND a.go_live_time <= UNIX_TIMESTAMP(NOW())
                                        AND a.grace_period = 0
                                    GROUP BY a.topic_num, a.camp_num
                                ) i
                                ON h.topic_num = i.topic_num 
                                AND h.camp_num = i.camp_num 
                                AND h.go_live_time = i.camp_live_time
                                LIMIT 200
                                )
                                SELECT JSON_ARRAYAGG(
                                    JSON_OBJECT(
                                    d.h_level, JSON_OBJECT(
                                        'topic_num', d.topic_num,
                                        'topic_name', t.topic_name,
                                        'camp_num', d.camp_num,
                                        'camp_name', e.camp_name,
                                        'camp_link', CONCAT('topic/',d.topic_num,'-',REPLACE(t.topic_name,' ','-'),'/',d.camp_num,'-',REPLACE(e.camp_name,' ','-')),
                                        'go_live_time', d.statement_live_time
                                    )
                                    )
                                ) AS json_response INTO var_statement_parent_hierarchy
                                FROM ParentHierarchy d INNER JOIN camp e
                                ON d.camp_num = e.camp_num
                                AND d.camp_live_time = e.go_live_time
                                INNER JOIN
                                (
                                    SELECT 
                                    a.topic_name,
                                    a.topic_num
                                    FROM
                                    topic a
                                    JOIN
                                    (
                                        SELECT
                                            topic_num,
                                            MAX(go_live_time) AS topic_live_time
                                        FROM
                                            topic
                                        WHERE
                                            topic_num = var_statement_topic_num
                                            AND objector_nick_id IS NULL
                                            AND go_live_time <= UNIX_TIMESTAMP (NOW())
                                            
                                        GROUP BY 
                                            topic_num
                                    ) b
                                    ON a.topic_num = b.topic_num
                                    AND a.go_live_time = b.topic_live_time
                                )t
                                ON d.topic_num = t.topic_num
                                WHERE d.topic_num = var_statement_topic_num
                                ORDER BY h_level;
                            
                                -- UPDATE Statement RECORDS IN  TABLE
                                
                                UPDATE elasticsearch_data
                                SET breadcrumb_data = var_statement_parent_hierarchy
                                WHERE topic_num = var_statement_topic_num
                                AND camp_num = var_statement_camp_num
                                AND go_live_time = var_statement_live_time
                                AND `type` = 'statement';
                                
                            IF done THEN
                                    CLOSE statement_breadcrumb_curs ;
                                    LEAVE read_loop ;
                                END IF ;
                            END LOOP read_loop ;
                            
                            -- INSERT NICKNAME DATA IN  TABLE
                            
                            INSERT INTO
                            elasticsearch_data
                            SELECT 
                                CONCAT('nickname-',a.id) AS id,
                                nick_name AS type_name,
                                0 AS topic_num,
                                0 AS camp_num,
                                '' AS go_live_time,
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
                            -- RETURN ALL ROWS FROM  TABLE
                            
                            SELECT
                            `type`,
                            id,
                            type_value,
                            topic_num,
                            camp_num,
                            DATE_FORMAT(FROM_UNIXTIME(go_live_time),'%d %M %Y, %h:%i:%s %p') AS go_live_time,
                            statement_num,
                            nick_name_id,
                            namespace,
                            link,
                            breadcrumb_data,
                            support_count
                            FROM elasticsearch_data 
                            ORDER BY `type`, topic_num, camp_num;
                            
                            SET SQL_SAFE_UPDATES = 1;
            
            
        END;";

        \DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('elastic_search_table_procedure');
    }
}

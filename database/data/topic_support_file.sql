CREATE TABLE `topic_support` (
                                 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                                 `topic_num` int(11) NOT NULL,
                                 `nick_name_id` int(11) NOT NULL,
                                 `delegate_nick_id` int(11) NOT NULL,
                                 `submit_time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                                 PRIMARY KEY (`id`),
                                 KEY `topic_support_topic_num_index` (`topic_num`),
                                 KEY `topic_support_nick_name_id_index` (`nick_name_id`),
                                 KEY `topic_support_delegate_nick_id_index` (`delegate_nick_id`)
) ENGINE=InnoDB AUTO_INCREMENT=870 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
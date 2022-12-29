<?php

return [
    'mind_expert_topic_num' => "81",
    'per_page' => "10",
    'thread_type' => [
        "allThread" => "all",
        "myThread" => "my",
        "myPrticipate" => "participate",
        "top10" => "most_replies",
    ],
    'APP_URL_FRONT_END'=>env('APP_URL_FRONT_END'),
    'notification_type' => [
        "Topic" => "Topic",
        "Camp" => "Camp",
        "Thread" => "Thread",
        "Post" => "Post",
        "Statement" => "Statement",
        "Support" => "Support",
        "addSupport" => "add",
        "addDelegate" => "add-delegate",
        "statementCommit" => "statement-commit",
        "campCommit" => "camp-commit",
        "topicCommit" => "topic-commit",
        "objectCamp" => "camp-object",
        "objectTopic" => "topic-object",
        "objectStatement" => "statement-object",
        "manageCamp" => "manage-camp",
    ],
    'emoji_unicodes' => array( '1F600','1F603','1F604','1F601','1F606','1F605','1F923','1F602','1F642','1F643'),
    'notify' => [
        "email" => 0,
        "push_notification" => 1,
        "both" => 2,
    ],
];

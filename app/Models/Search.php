<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Search extends Model
{
   
    protected $table = 'elasticsearch_data';

    protected $casts = [
        'id' => 'string',
        'type' => 'string',
        'type_value' => 'string',
        'topic_num' => 'integer',
        'camp_num' => 'integer',
        'go_live_time' => 'string',
        'nick_name_id' => 'integer',
        'namespace' => 'string',
        'link' => 'string',
        'statement_num' => 'integer',
        'breadcrum_data' => 'json',
        'support_count' => 'double'
    ];

    protected $fillable = ['id', 'type', 'type_value','topic_num', 'camp_num', 'go_live_time', 'nick_name_id', 'namespace', 'link', 'statement_num', 'breadcrum_data', 'support_count'];
}

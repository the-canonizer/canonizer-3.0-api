<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class TopicTagFailure extends Model
{

    protected $table = 'topic_tag_failures';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num', 'fail_to_associate_tag_reason'];
}

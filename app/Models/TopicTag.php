<?php

namespace App\Models;

use App\Jobs\ForgetCacheKeyJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TopicTag extends Model
{
    protected $dateFormat = 'U';
    
    protected $table = 'topics_tags';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['topic_num','tag_id','created_at', 'updated_at'];

    public static function boot()
    {
        static::saved(function($item) {
            //forget cache
            self::forgetCache($item);
        });

        parent::boot();
    }

    public static function forgetCache($item)
    {
        $cacheKeysToRemove = [
            'live_topic_default-' . $item->topic_num,
            'live_topic_review-' . $item->topic_num
        ];
        foreach ($cacheKeysToRemove as $key) {
            Cache::forget($key);
        }
        if ($item->go_live_time > time()) {
            dispatch(new ForgetCacheKeyJob($cacheKeysToRemove, Carbon::createFromTimestamp($item->go_live_time)));
        }
    }

    
    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id'); // Assuming tag_id is the foreign key
    }

    /**
     * Returns an array of tag IDs associated with the given topic number.
     *
     * @param int $topicNum The topic number to retrieve tags for.
     * @return array An array of tag IDs.
     */
    public static function getRelatedTagIds($topicNum) {
        $tags = self::where('topic_num', $topicNum)->pluck('tag_id')->toArray();
        return $tags ?? [];
    }
}

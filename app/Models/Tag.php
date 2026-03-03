<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tag extends Model
{
    use HasFactory;

    protected $dateFormat = 'U';

    protected $table = 'tags';

    public $timestamps = false;

    protected $fillable = ['title', 'is_active', 'parent_id', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function topics()
    {
        return $this->belongsToMany(Topic::class, 'topics_tags', 'tag_id', 'topic_num');
    }

    public static function updateOrCreateTopicTags($tags, $topicNum)
    {
        try {
            foreach ($tags as $tagId) {
                TopicTag::updateOrCreate(
                    ['topic_num' => $topicNum, 'tag_id' => $tagId], // Unique criteria
                    [] // No additional attributes to update (optional)
                );
            }

            // After update of record , in case of any removal of tags we need to remove from topic_tags...
            TopicTag::whereNotIn('tag_id', $tags)->where('topic_num', $topicNum)->delete();

            return true;
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }

    public static function getTagsByTopicNums($topicNums)
    {
        return self::select('tags.*', 'topics_tags.topic_num')
            ->join('topics_tags', 'tags.id', '=', 'topics_tags.tag_id')
            ->whereIn('topics_tags.topic_num', $topicNums)
            ->get()
            ->groupBy('topic_num');
    }
}

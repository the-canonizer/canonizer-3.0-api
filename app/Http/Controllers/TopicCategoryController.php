<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Http\Resources\ErrorResource;
class TopicCategoryController extends Controller
{
    public function index()
    {
        $categories = TopicCategory::orderBy('name')->get(['id', 'name', 'description']);
        return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $categories, '');
    }

    public function assign(Request $request)
    {
        $request->validate([
            'topic_num'   => 'required|integer',
            'category_id' => 'required|integer|exists:topic_categories,id',
        ]);

        $affected = Topic::where('topic_num', $request->topic_num)
            ->update(['category_id' => $request->category_id]);

        if ($affected === 0) {
            return $this->resProvider->apiJsonResponse(404, 'Topic not found', '', '');
        }

        $topic = Topic::where('topic_num', $request->topic_num)
            ->latest('submit_time')
            ->first();
        if ($topic) {
            Topic::updateElasticSearch($topic);
        }

        return $this->resProvider->apiJsonResponse(
            200,
            'Category updated',
            ['topic_num' => (int) $request->topic_num, 'category_id' => (int) $request->category_id],
            ''
        );
    }

    public function adminTopicList(Request $request)
    {
        $rows = \DB::table('topic as t')
            ->select(
                't.topic_num',
                \DB::raw('MAX(t.topic_name) as topic_name'),
                \DB::raw('MAX(t.category_id) as category_id'),
                \DB::raw('MAX(t.is_sandbox) as is_sandbox'),
                \DB::raw('MAX(c.name) as category_name'),
                \DB::raw('MIN(t.submit_time) as first_seen')
            )
            ->leftJoin('topic_categories as c', 't.category_id', '=', 'c.id')
            ->groupBy('t.topic_num')
            ->orderByDesc('first_seen')
            ->get();

        return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $rows, '');
    }
}

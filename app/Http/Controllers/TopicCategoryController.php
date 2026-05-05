<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\TopicCategory;
use App\Http\Resources\ErrorResource;
use App\Helpers\ResponseInterface;

class TopicCategoryController extends Controller
{
    public ResponseInterface $resProvider;

    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }

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
}

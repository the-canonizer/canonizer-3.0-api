<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ElasticSearch;
use App\Models\Search;
use DB;
use App\Helpers\ResponseInterface;

class SearchController extends Controller
{
    
    public function __construct(ResponseInterface $respProvider)
    {
       $this->resProvider = $respProvider;
    }

    public function getSearchResults(Request $request)
    {
        $term = $request->get('term');
        $type = $request->get('type') ?? '';
        $size = $request->get('size') ?? 0;
        $page = $request->get('page') ?? 1;
        $totalCounts = [];
        try{
            // Define types when $type is empty or not set
            $typesToSearch = isset($type) && empty(trim($type)) ? ['topic', 'camp', 'statement', 'nickname'] : [$type];
            $data = [];
            $totalCounts = [];
            $total = 0;
            $search_ids=[];

            foreach ($typesToSearch as $searchType) {
                $result = Search::getSearchData($term, [$searchType], $size, $page);
                $data[$searchType] = $result['data'];
                $totalCounts[$searchType] = $result['count'];
                $total += $result['count'];
            }
            
            if(count($typesToSearch) ==  1){
                $all_size=10000;
                $all_page=0;
                $search_ids = self::getSearchIds($term, $type,$all_size, $all_page);
            }

            $response = self::optimizeResponse($data, $total, $page, $size, $search_ids, $totalCounts);
            $status = 200;
            $message =  trans('message.success.success');
            return $this->resProvider->apiJsonResponse($status, $message, $response, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

    public static function optimizeResponse($data, $total, $page, $size, $search_ids, $totalCounts = [] )
    { 
        return $response = [
            'data' => $data,
            'meta_data' => [
                'total' => $total,
                'page' => $page,
                'size' => $size,
                'topic_total' => isset($totalCounts['topic']) ? $totalCounts['topic'] : 0,
                'camp_total'  => isset($totalCounts['camp']) ? $totalCounts['camp'] : 0,
                'statement_total' =>isset($totalCounts['statement']) ? $totalCounts['statement'] : 0,
                'nickname_total' => isset($totalCounts['nickname']) ? $totalCounts['nickname'] : 0,
                'search_ids'=> $search_ids
            ]
        ];
    }
    
    public function advanceSearchFilter(Request $request)
    {
        $all        = $request->all();
        $type       = $all['type'];
        $nickIds    = $all['nick_ids'] ?? [];   //advance filter serach query on nickname
        $search     = $all['search'];
        $algorithm  = $all['algo'] ??  '';
        $asof       = $all['asof'] ??  '';   //search type
        $score      = $all['score'] ??  0;
        $query      = $all['query'] ?? '';
        $campIds    = $all['camp_ids'] ?? [];
        $topicIds   = $all['topic_ids'] ?? [];
        $pageNumber = $all['page_number'] ?? 1;
        $pageSize   = $all['page_size'] ?? 20;
        $asofdate   = $all['asofdate'] ?? time();

        $status = 200;
        $message =  trans('message.success.success');
        switch ($type) {
            case 'nickname':
                $response['topic'] = Search::advanceTopicFilterByNickname($nickIds, $query);
                $response['camp']  = Search::advanceCampFilterByNickname($nickIds, $query);
                break;
            case 'camp':
                $response['camp'] = [];
                if(!empty($topicIds) || !empty($campIds)){
                    $result = Search::advanceCampSearch($topicIds, $campIds, $asof, $asofdate, $search, $pageNumber, $pageSize); 
                    $response['camp']  = $result['data'];
                    $response['camp_total'] = $result['total'];
                }
                break;
            case 'topic':
                $response['topic'] = [];
                if(!empty($topicIds)){
                    $result = Search::advanceTopicSearch($topicIds, $campIds, $asof, $asofdate, $search, $pageNumber, $pageSize);
                    $response['topic']  = $result['data'];
                    $response['topic_total'] = $result['total'];
                }
                break;
            case 'statement':
                $response['statement'] = [];
                if(!empty($topicIds) && !empty($campIds)){
                    $result = Search::advanceStatementSearch($topicIds, $campIds, $asof, $asofdate, $search, $pageNumber, $pageSize);
                    $response['statement'] = $result['data'];
                    $response['statement_total'] = $result['total'];
                }
                break;
            default:
                // Do something if none of the above cases match
                break;
        }
        return $this->resProvider->apiJsonResponse($status, $message, $response, null);
    }

    public function getSearchIds($term, $type, int $size = 10000, int $page = 0)
    {
        try {
            $camp_ids='';
            $result = Search::getSearchData($term, [$type], $size, $page);
            if($type == 'topic'){
                $topic_ids =  collect($result['data'])->pluck('id')->map(function ($id) { 
                        preg_match('/\d+/', $id, $matches);
                        return $matches[0] ?? null; 
                    })->filter()->implode(',');
            }
            if($type == 'camp' || $type == 'statement'){
                $camp_ids =  collect($result['data'])->pluck('camp_num')->implode(',');
                $topic_ids = collect($result['data'])->pluck('topic_num')->implode(',');
            }

            return ['camp_ids' => $camp_ids, 'topic_ids' => $topic_ids];

        } catch (\Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }
    
}

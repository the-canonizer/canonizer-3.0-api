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
        $size = $request->get('size') ?? 25;
        $page = $request->get('page') ?? 1;
        $totalCounts = [];
        try{

            if(isset($type) && empty(trim($type)))
            {
                //$type = ['topic','camp','statement','nickname'];
                $topic = Search::getSearchData($term, ['topic'], $size, $page);
                $camp = Search::getSearchData($term, ['camp'], $size, $page);
                $statement = Search::getSearchData($term, ['statement'], $size, $page);
                $nickName = Search::getSearchData($term, ['nickname'], $size, $page);  
                
                $data['topic'] = $topic['data'];
                $data['camp'] = $camp['data'];
                $data['statement'] = $statement['data'];
                $data['nickname'] = $nickName['data'];
                $total = $topic['count'] + $camp['count'] + $statement['count'] + $nickName['count'];                

                $totalCounts['topic']       = isset($topic['type_counts']['topic']) ? $topic['type_counts']['topic']  : 0;
                $totalCounts['camp']        = isset($camp['type_counts']['camp']) ? $camp['type_counts']['camp'] : 0;
                $totalCounts['statement']   = isset($statement['type_counts']['statement']) ? $statement['type_counts']['statement'] :0;
                $totalCounts['nickname']    = isset($nickName['type_counts']['nickname']) ? $nickName['type_counts']['nickname'] : 0;

            }else{
                $searchData = Search::getSearchData($term, [$type], $size, $page);
                $data[$type] = $searchData['data'];
                $total = $searchData['count'];

                $totalCounts[$type] = isset($searchData['type_counts'][$type]) ? $searchData['type_counts'][$type]  : 0;
            } 

            $response = self::optimizeResponse($data, $total, $page, $size, $totalCounts);
            $status = 200;
            $message =  trans('message.success.success');
            
            return $this->resProvider->apiJsonResponse($status, $message, $response, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
        
        

        return ($result);
    }

    public static function optimizeResponse($data, $total, $page, $size, $totalCounts = [])
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
        $campIds    = $all['camp_ids'] ?? '';
        $topicIds   = $all['topic_ids'] ?? '';
        $pageNumber = $all['page_number'] ?? 1;
        $pageSize   = $all['page_size'] ?? 2;
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
                    $response['camp'] = Search::advanceCampSearch($topicIds, $campIds, $asof, $asofdate); 
                }
                break;
            case 'topic':
                $data = Search::advanceTopicSearch($search, $algorithm, $asof, $score, $asofdate, $pageNumber, $pageSize);
                $status = $data['code'];
                $message = $data['message'];
                $response['topic'] = $data['data'];
                break;
            case 'statement':
                $response['statement'] = [];
                if(!empty($topicIds) && !empty($campIds)){
                    $response['statement'] = Search::advanceStatementSearch($topicIds, $campIds, $asof, $asofdate);
                }
               break;
            default:
                // Do something if none of the above cases match
                break;
        }

        
        
        return $this->resProvider->apiJsonResponse($status, $message, $response, null);
    }

}

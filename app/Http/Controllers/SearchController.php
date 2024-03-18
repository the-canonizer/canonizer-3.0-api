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

                //$data =  self::optimizeResponse($searchData,'all',$page,$size);
            
            }else{
                $searchData = Search::getSearchData($term, [$type], $size, $page);
                $data[$type] = $searchData['data'];
                $total = $searchData['count'];

              //  =  self::optimizeResponse($searchData, $type,$page,$size);
            } 

            $response = self::optimizeResponse($data, $total, $page, $size);

            //$data = $data;
            $status = 200;
            $message =  trans('message.success.success');
            
            return $this->resProvider->apiJsonResponse($status, $message, $response, null);
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
        
        

        return ($result);
    }

    public static function optimizeResponse($data, $total, $page, $size)
    { 
        
        
       return $response = [
                'data' => $data,
                'meta_data' => [
                    'total' => $total,
                    'page' => $page,
                    'size' => $size
                ]
        ];
    }

    public function advanceSearchFilter(Request $request)
    {
        $all    = $request->all();
        $type   = $all['type'];
        $nickIds  = $all['nick_ids'] ?? [];   //advance filter serach query on nickname
        $search = $all['search'];
        $algo   = $all['algo'] ??  '';
        $asof   = $all['asof'] ??  '';   //search type
        $score  = $all['score'] ??  0;
        $query = $all['query'] ?? '';

        switch ($type) {
            case 'nickname':
                $response['topic'] = Search::advanceTopicFilterByNickname($nickIds, $query);
                $response['camp'] = Search::advanceCampFilterByNickname($nickIds, $query);
                
                break;
            case 'option2':
                // Do something for option 2
                break;
            case 'option3':
                // Do something for option 3
                break;
            default:
                // Do something if none of the above cases match
                break;
        }

        $status = 200;
        $message =  trans('message.success.success');
        
        return $this->resProvider->apiJsonResponse($status, $message, $response, null);
    }

}

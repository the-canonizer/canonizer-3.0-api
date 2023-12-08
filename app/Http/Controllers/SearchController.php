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
        /*if(isset($type) && $type != 'all'){
            $array[$type] = $data['data'];
        }else{
            $array =  [
                'topic' => [],
                'camp' => [],
                'statement' => [],
                'nickname' => []
            ]; 
            foreach($data['data'] as $es)
            {
                
                switch ($es['type']) {
                    case 'topic':
                        array_push($array['topic'], $es);
                        break;
                    case 'camp':
                        array_push($array['camp'], $es);
                    break;
                    case 'statement':
                        array_push($array['statement'], $es);
                    break;
                    case 'nickname':
                        array_push($array['nickname'], $es);
                        break;
                    
                    default:
                        break;
                }
            }
        }*/
    }

}

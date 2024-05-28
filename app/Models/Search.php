<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\ElasticSearch;
use DB;
use App\Models\Topic;
use App\Models\Camp;
use App\Helpers\SupportAndScoreCount;
use App\Facades\Util;

class Search extends Model
{
   
    protected $table = 'elasticsearch_data';
    public $timestamps = false;

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

    public static function getSearchData($search, $type, $size = 25, $from = 1)
    {
        $elasticsearch = (new Elasticsearch())->elasticsearchClient;
        $size = (intval($size) ?: 25);
        $from = $size * ((intval($from) ?: 1) - 1);

        $searchFields = ['type_value'];
        //$excludesColumn = ["id","type","breadcrumb"];


        $indexName = 'canonizer_elastic_search';
        $response = $elasticsearch->search([
            'index' => $indexName,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'multi_match' => [
                                                'query' => $search, // Use the custom query value here
                                                'fields' => $searchFields,
                                            ],
                                        ],
                                        [
                                            'multi_match' => [
                                                'query' => $search, // Use the custom query value here
                                                'type' => 'phrase_prefix',
                                                'fields' => $searchFields,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'filter' => [
                            [
                                'terms' => [
                                    'type' => $type, // Use the custom type value here
                                ],
                            ],
                        ],
                    ],
                ],
                'size' => $size, // Use the custom size value here
                'from' => $from, // Use the custom from value here
            ],
        ]);

        if (isset($response['hits']['hits']) && isset($response['hits']['total']['value'])) {
            $parsedResponse = $response['hits']['hits'];
            $totalResponse = $response['hits']['total']['value'];
        
            $data = [
                'data' => collect($parsedResponse)->pluck('_source'),
                'type' => $type,
                'count' => $totalResponse,
            ];
        
            return $data;
        } else {
            // Handle the case where the Elasticsearch response doesn't contain the expected data.
            $data = [
                'data' => [],
                'type' => '',
                'count' => 0
            ]; 
            
            return $data;
        }

    }


    public static function createOrUpdate($id, $type, $typeValue, $topicNum = 0, $campNum = 0, $link, $goLiveTime = 0, $namespace = null, $breadcrumb = '', $statementNum = '', $nickNameId = '', $supportCount = '')
    {       
        if($type == 'nickname'){
            $queryArray = ['type' => 'nickname','nick_name_id' => $nickNameId];  
          }else if($type == 'statement'){
              $queryArray = ['type' => 'statement','statement_num'=>$statementNum];   
          }else{
              $queryArray = ['type' => $type,'topic_num' => $topicNum, 'camp_num' => $campNum];   
          }
          $modelEvent = 'create';
          $search = Search::updateOrCreate($queryArray);
          if(!empty($search)){
            $modelEvent = 'update';
          }
          $search->id = $id;
          $search->type = $type;
          $search->type_value = $typeValue;
          $search->topic_num = $topicNum;
          $search->camp_num = $campNum;
          $search->statement_num = $statementNum;
          $search->nick_name_id = $nickNameId;
          $search->go_live_time = $goLiveTime;
          $search->namespace = $namespace;
          $search->link = $link;
          $search->breadcrumb_data = $breadcrumb;
          $search->support_count = $supportCount;
          if($modelEvent == 'update'){
            $data = [
                'id' => $id,
                'type' => $type,
                'type_value' => $typeValue,
                'topic_num' => $topicNum,
                'camp_num' => $campNum,
                'statement_num' => $statementNum,
                'go_live_time' => $goLiveTime,
                'namespace' => $namespace,
                'link' => $link,
                'breadcrumb_data' => json_encode($breadcrumb),
                'nick_name_id' => $nickNameId,
                'support_count' => $supportCount
            ];
            Search::where($queryArray)
                    ->update($data);
            return;

          }
          $search->save();
          return;


    }

    public static function deleteRecordIfExist($id)
    {
        Search::where(['id'=>$id])->delete();
        return;
    }

    /**
     * Return breadcrum data for elastic search 
     */
    public static function getCampBreadCrumbData($liveTopic, $topicNum, $campNum)
    {
        $filter['topicNum'] = $topicNum;
        $filter['campNum'] = $campNum;
        $livecamp = Camp::getLiveCamp($filter);
        $breadcrumb = array_reverse(Camp::campNameWithAncestors($livecamp, $filter));
        $data = [];
        $tempdata = [];
        foreach($breadcrumb as $k => $bd)
        {
            
            $temp[$k+1] = [
                'camp_num' =>  $bd['camp_num'],
                'camp_link' => Camp::campLink($bd['topic_num'], $bd['camp_num'], $liveTopic->topic_name, $bd['camp_name'], true),
                'camp_name' => $bd['camp_name'],
                'topic_num' => $bd['topic_num'],
                'topic_name' => $liveTopic->topic_name

            ];
            $tempdata[$k] = $temp;
        }

        // Convert the JSON object to a JSON array
        $jsonArray = array_values($tempdata);

        // Encode the resulting JSON array
        $jsonString = json_encode($jsonArray, JSON_UNESCAPED_SLASHES);
        return $jsonString;
    }

    /**
     * 
     */
    public static function advanceTopicFilterByNickname($nickIds, $query)
    {
        $results = DB::table('topic as t')
            ->select('t.topic_num', 't.topic_name', 't.id')
            ->join(DB::raw('(
                    SELECT topic_num 
                    FROM support 
                    WHERE nick_name_id IN (' . implode(',', $nickIds) . ')
                    AND end = 0 
                    AND camp_num = 1
                ) AS s'), 't.topic_num', '=', 's.topic_num')
            ->join(DB::raw('(
                    SELECT topic_num,
                        MAX(go_live_time) AS live_time
                    FROM topic
                    WHERE objector_nick_id IS NULL
                    AND go_live_time <= UNIX_TIMESTAMP(NOW())
                    GROUP BY topic_num
                ) AS b'), function ($join) use ($query) {
                    $join->on('t.topic_num', '=', 'b.topic_num')
                        ->where('t.go_live_time', '=', DB::raw('b.live_time'))
                        ->where('t.topic_name', 'like', '%' . $query . '%');
                })
            ->get();

            $topic = self::processResults($results,'topic');

        return $topic;



       /* $sql = "SELECT t.topic_num, t.topic_name,t.id
        FROM topic t
        INNER JOIN (
            SELECT topic_num 
            FROM support 
            WHERE nick_name_id in (357) 
            AND end = 0 
            AND camp_num = 1
        ) s ON t.topic_num = s.topic_num
        INNER JOIN (
            SELECT topic_num,
                   MAX(go_live_time) AS live_time
            FROM topic
            WHERE objector_nick_id IS NULL
            AND go_live_time <= UNIX_TIMESTAMP(NOW())
            GROUP BY topic_num
        ) b ON t.topic_num = b.topic_num AND t.go_live_time = b.live_time AND t.topic_name like '%algo%'";*/
    }

    public static function processResults($data,$type)
    {
        $topic = [];
        foreach($data as $dt)
        {

            $temp['title'] = $dt->topic_name;
            $temp['topic_num'] = $dt->topic_num;
            $temp['camp_num'] = 1;
            $temp['camp_name'] = 'Agreement';
            $temp['link'] = Topic::topicLink($dt->topic_num, 1, $dt->topic_name, 'Agreement', true);
            array_push($topic, $temp);
        }

        return $topic;
    }

    public static function advanceCampFilterByNickname($nickIds, $query)
    {
        $results = DB::table('camp as a')
                    ->select('a.camp_name', 'a.topic_num', 'a.camp_num', 'a.go_live_time')
                    ->join(DB::raw('(SELECT topic_num, camp_num
                                    FROM support 
                                    WHERE nick_name_id IN (' . implode(',', $nickIds) . ')
                                    AND end = 0 
                                    AND camp_num != 1) as s'), function ($join) {
                        $join->on('a.topic_num', '=', 's.topic_num')
                            ->on('a.camp_num', '=', 's.camp_num');
                    })
                    ->join(DB::raw('(SELECT topic_num, camp_num, MAX(go_live_time) AS live_time
                                    FROM camp
                                    WHERE objector_nick_id IS NULL
                                    AND go_live_time <= UNIX_TIMESTAMP(NOW())
                                    AND grace_period = 0
                                    GROUP BY topic_num, camp_num) as b'), function ($join) {
                        $join->on('a.topic_num', '=', 'b.topic_num')
                            ->on('a.camp_num', '=', 'b.camp_num');
                    })
                    ->where('a.is_archive', 0)
                    ->where('a.go_live_time', DB::raw('b.live_time'))
                    ->where('a.camp_name', 'like', '%' . $query . '%')
                    ->get();

         $camps = [];

         foreach($results as $result)
         {
            $topicNum = $result->topic_num;
            $campNum  = $result->camp_num;
            $liveTopic = Topic::getLiveTopic($topicNum);  
            $temp['camp_num'] = $result->camp_num;
            $temp['topic_num'] = $topicNum;
            $temp['title'] = $result->camp_name;
            $temp['link'] = Camp::campLink($topicNum, $result->camp_num, $liveTopic->topic_name, $result->camp_name, true);
            $breadcrumb = self::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
            $temp['breadcrumb'] = $breadcrumb;
            array_push($camps, $temp);
         }


        return $camps;
    }


    public static function advanceCampSearch($topicIds, $campIds, $algorithm, $score = 0, $asof = 'default', $asofdate = '')
    {
        $asofdate = ($asofdate) ? $asofdate : time();
        $query = DB::table('camp as a')
                ->select('a.id', 'a.camp_name', 'a.topic_num', 'a.camp_num', 'a.go_live_time')
                ->join(DB::raw('(SELECT
                            topic_num,
                            camp_num,
                            MAX(go_live_time) AS live_time
                        FROM
                            camp
                        WHERE
                            objector_nick_id IS NULL
                            AND grace_period = 0
                            AND is_archive = 0
                            AND topic_num IN (' . implode(',', $topicIds) . ')
                            AND camp_num IN (' . implode(',', $campIds) . ')
                        GROUP BY
                            topic_num,
                            camp_num) b'), function ($join) {
                    $join->on('a.topic_num', '=', 'b.topic_num')
                         ->on('a.camp_num', '=', 'b.camp_num')
                         ->on('a.go_live_time', '=', 'b.live_time');
                });

                if ($asof != 'review') {
                    $query->where('b.live_time', '<=', $asofdate);
                }else{
                    $query->where('b.live_time', '>=', $asofdate);
                }
                $results = $query->get();

                $data = [];
                //$algorithm = 'blind_popularity';
                foreach($results as $result)
                {
                   
                    $topicNum = $result->topic_num;
                    $campNum = $result->camp_num;
                   
                    $supportCount = new SupportAndScoreCount();
                    $scoreData = $supportCount->getCampTotalSupportScore($algorithm, $topicNum, $campNum, $asofdate,'default');
                    $scoreCount = $scoreData['score'];
                    if($scoreCount < $score){
                        continue;
                    }
                    $liveTopic = Topic::getLiveTopic($topicNum);
                    $breadcrumb = self::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
                    $temp['topic_num'] = $topicNum;
                    $temp['camp_num'] = $campNum;
                    $temp['camp_name'] = $result->camp_name;
                    $temp['breadcrumb'] = $breadcrumb;
                    $temp['score'] = $scoreCount;

                    array_push($data,$temp);
                }

            return $data;
    }


    public static function advanceTopicSearch($search, $algorithm, $asof, $filter, $asofdate='', $page_number = 1, $page_size = 5)
    {
        $requestBody = [
            'algorithm'     =>  $algorithm,
            'search'        =>  $search,
            'asof'          =>  $asof,
            'asofdate'      =>  ($asofdate) ? $asofdate : time(),
            'filter'        =>  $filter,
            'page_number'   =>  $page_number,
            'page_size'     =>  $page_size,
            'namespace_id'  =>  "",
        ];
        
        $endpointCSGetdata = env('CS_GET_HOME_PAGE_DATA'); 
        $appURL = env('CS_APP_URL');
        $apiToken = env('API_TOKEN');

        if(empty($appURL) || empty($endpointCSGetdata) || empty($apiToken)) {
            Log::error("App url or endpoints or API Token of store tree is not defined");
            return;
        }
        $endpoint = $appURL."/".$endpointCSGetdata;
        $headers = []; // Prepare headers for request
        $headers[] = 'Content-Type:multipart/form-data';
        $headers[] = 'X-Api-Token:'.$apiToken.'';
        $response = Util::execute('POST', $endpoint, $headers, $requestBody);

        // Check the unauthorized request here...
        if(isset($response)) {
            $checkRes = json_decode($response, true);
            if(array_key_exists("status_code", $checkRes) && $checkRes["status_code"] == 401) {
                Log::error("Unauthorized action.");
                throw new ServiceAuthenticationException('Authentication Issue!');
                return;
            }
        }
        if(isset($response)) {
            $responseData = json_decode($response, true)['data'];
            $responseMessage = json_decode($response, true)['message'];
            $responseCode = json_decode($response, true)['status_code'] ? json_decode($response, true)['status_code'] : 404;
            //echo "<pre>"; print_r($response); exit;
            //process the respponse
            $topics = [];
            foreach($responseData['topic'] as $topic){
                $namespace = Namespaces::find($topic['namespace_id']);
                //$liveTopic = Topic::getLiveTopic($topic['topic_id']);
                $temp['topic_num']     = $topic['topic_id'];
                $temp['topic_name']    = $topic['topic_name'];
                $temp['camp_num']      = 1; 
                $temp['namespace']     = $namespace->name;
                $temp['link']          = Topic::topicLink($topic['topic_id'], 1, $topic['topic_name'], 'Agreement', true);


                array_push($topics, $temp);

            }
            return $data = [ 
                'data' => $topics,
                'code' => $responseCode,
                'message' => $responseMessage
            ];
            return $data;
        } else {
            Log::error("Empty response, something went wrong");
        }
    }

    public static function advanceStatementSearch($topicIds, $campIds, $algorithm, $score = 0, $asof = 'default', $asofdate = '')
    {
        $asofdate = (!empty($asofdate)) ? strtotime($asofdate) : time();
        $query = DB::table('statement as a')
                ->select('a.id', 'a.parsed_value as type_value', 'a.topic_num', 'a.camp_num', 'a.go_live_time', 'c.camp_name')
                ->join(DB::raw('(SELECT
                            topic_num,
                            camp_num,
                            MAX(go_live_time) AS live_time
                        FROM
                        statement
                        WHERE
                            objector_nick_id IS NULL
                            AND grace_period = 0
                            AND topic_num IN (' . implode(',', $topicIds) . ')
                            AND camp_num IN (' . implode(',', $campIds) . ')
                        GROUP BY
                            topic_num,
                            camp_num) b'), function ($join) {
                    $join->on('a.topic_num', '=', 'b.topic_num')
                        ->on('a.camp_num', '=', 'b.camp_num')
                        ->on('a.go_live_time', '=', 'b.live_time');
                });

                $query->join(DB::raw('(SELECT
                            topic_num,
                            camp_num,
                            MAX(go_live_time) AS live_time,
                            camp_name
                        FROM
                        camp
                        WHERE
                            objector_nick_id IS NULL
                            AND is_archive = 0
                            AND grace_period = 0
                            AND topic_num IN (' . implode(',', $topicIds) . ')
                            AND camp_num IN (' . implode(',', $campIds) . ')
                        GROUP BY
                            topic_num,
                            camp_num) c'), function ($join) {
                    $join->on('a.topic_num', '=', 'c.topic_num')
                        ->on('a.camp_num', '=', 'c.camp_num');
                });

                if ($asof != 'review') {
                    $query->where('b.live_time', '<=', $asofdate);
                }else{
                    $query->where('b.live_time', '>=', $asofdate);
                }
                $results = $query->get();


                $data = [];
                //$algorithm = 'blind_popularity';
                foreach($results as $result)
                {
                   
                    $topicNum = $result->topic_num;
                    $campNum = $result->camp_num;
                   
                    $supportCount = new SupportAndScoreCount();
                    $scoreData = $supportCount->getCampTotalSupportScore($algorithm, $topicNum, $campNum, $asofdate,'default');
                    $scoreCount = $scoreData['score'];
                    if($scoreCount < $score){
                        continue;
                    }
                    $liveTopic = Topic::getLiveTopic($topicNum);
                    $breadcrumb = self::getCampBreadCrumbData($liveTopic, $topicNum, $campNum);
                    $temp['topic_num'] = $topicNum;
                    $temp['camp_num'] = $campNum;
                    $temp['camp_name'] = $result->camp_name;
                    $temp['breadcrumb'] = $breadcrumb;
                    $temp['score'] = $scoreCount;
                    $temp['type_value'] = $result->type_value;


                    array_push($data,$temp);
                }

            return $data;
    }

}

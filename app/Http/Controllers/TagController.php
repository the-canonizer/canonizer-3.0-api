<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Http\Request\Validate;
use App\Facades\Util;
use App\Helpers\ResponseInterface;
use App\Helpers\ResourceInterface;
use App\Http\Request\ValidationRules;
use App\Http\Request\ValidationMessages;
use App\Http\Resources\ErrorResource;
use App\Models\Tag;

class TagController extends Controller
{
    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Post(path="/get-tags-list",
     *   tags={"Tags"},
     *   summary="Get tags",
     *   description="This is used to get tags added by admin.",
     *   operationId="getTagsList",
     *   @OA\Parameter(
     *         name="Authorization",
     *         in="header",
     *         required=true,
     *         description="Bearer {access-token}",
     *         @OA\Schema(
     *              type="Authorization"
     *         ) 
     *   ),
     *   @OA\RequestBody(
     *       required=true,
     *       description="Get tags",
     *       @OA\MediaType(
     *           mediaType="application/x-www-form-urlencoded",
     *           @OA\Schema(
     *               @OA\Property(
     *                   property="per_page",
     *                   description="Number of records per page",
     *                   required=false,
     *                   type="integer",
     *               ),
     *               @OA\Property(
     *                   property="page",
     *                   description="page number",
     *                   required=false,
     *                   type="integer",
     *               )
     *               @OA\Property(
     *                   property="search_term",
     *                   description="search specific records",
     *                   required=false,
     *                   type="text",
     *               )
     *                @OA\Property(
     *                   property="sort_by",
     *                   description="sorting of records",
     *                   required=false,
     *                   type="ASC|DESC",
     *               )
     *           )
     *       )  
     *    ),
     *   @OA\Response(response=200, description="Success"),
     *   @OA\Response(response=400, description="Error message"),
     *   @OA\Response(response=401, description="Unauthenticated")
     *  )
     */

    public function getTagsList(Request $request, Validate $validate)
    {
        $validationErrors = $validate->validate($request, $this->rules->getTagsListingValidationRules(), $this->validationMessages->getTagsListingValidationMessages());
        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }
        $perPage = $request->per_page ?? config('global.per_page');
        try {
            $topic_tags = Tag::select('tags.*')
                ->selectSub(function ($query) {
                    $query->from('topics_tags')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('topics_tags.tag_id', 'tags.id');
                }, 'total_topics')
                ->when($request->search_term, function ($query, $result) {
                    $query->where(function ($q) use ($result) {
                        $q->where('title', 'LIKE', '%' . $result . '%');
                    });
                })->orderBy('id', $request->sort_by ?? 'DESC');
            
            if($request->has('per_page') && $request->has('page')) {
                $topic_tags = $topic_tags->paginate($perPage);
                $topic_tags = Util::getPaginatorResponse($topic_tags);
            } else {
                $topic_tags = $topic_tags->get();
                $topic_tags = (object) [
                    "items" => $topic_tags
                ];
            }
            return $this->resProvider->apiJsonResponse(200, trans('message.success.success'), $topic_tags, '');
        } catch (Exception $e) {
            return $this->resProvider->apiJsonResponse(400, trans('message.error.exception'), '', $e->getMessage());
        }
    }
}

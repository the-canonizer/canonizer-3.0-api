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
use App\Models\UserTag;

class TagController extends Controller
{
    private ValidationRules $rules;

    private ValidationMessages $validationMessages;

    public function __construct(ResponseInterface $respProvider, ResourceInterface $resProvider, ValidationRules $rules, ValidationMessages $validationMessages)
    {
        $this->rules = $rules;
        $this->validationMessages = $validationMessages;
        $this->resourceProvider  = $resProvider;
        $this->resProvider = $respProvider;
    }

    /**
     * @OA\Post(
     *     path="/get-tags-list",
     *     summary="Get tags",
     *     description="Retrieve tags added by the admin.",
     *     tags={"Tags"},
     *     operationId="getTagsList",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         description="Filter and paginate tags list",
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="per_page",
     *                     type="integer",
     *                     description="Number of records per page",
     *                     example=10
     *                 ),
     *                 @OA\Property(
     *                     property="page",
     *                     type="integer",
     *                     description="Page number for pagination",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="search_term",
     *                     type="string",
     *                     description="Search specific records",
     *                     example="technology"
     *                 ),
     *                 @OA\Property(
     *                     property="sort_by",
     *                     type="string",
     *                     enum={"asc", "desc"},
     *                     description="Sorting order",
     *                     example="asc"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="message", type="string", example="Validation error occurred"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
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
                ->selectSub(function ($query) {
                    $query->from('user_tags')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('user_tags.tag_id', 'tags.id');
                }, 'total_users')
                ->when($request->search_term, function ($query, $result) {
                    $query->where(function ($q) use ($result) {
                        $q->where('title', 'LIKE', '%' . $result . '%');
                    });
                })
                ->where('is_active', 1)
                ->orderBy('title', $request->sort_by ?? 'ASC');

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


    /**
     * @OA\POST(path="/create/user/tags",
     *   tags={"Tags"},
     *   summary="This API is used to associate a category with a user during registration",
     *   description="",
     *   operationId="createUserTags",
     *   @OA\RequestBody(
     *     required=true,
     *     description="Request Body JSON Parameter",
     *     @OA\MediaType(
     *          mediaType="application/json",
     *          @OA\Schema(
     *               @OA\Property(
     *                  property="user_id",
     *                  type="string",
     *                  description="ID of the user",
     *                  example="123"
     *              ),
     *              @OA\Property(
     *                  property="user_tags",
     *                  type="array",
     *                  description="List of tag IDs associated with the user",
     *                  @OA\Items(
     *                      type="integer",
     *                      example=5
     *                  )
     *              )
     *          )
     *     ),
     *   ),
     *   @OA\Response(
     *      response=200,
     *      description="Successful operation",
     *      @OA\JsonContent(
     *          type="object",
     *          @OA\Property(
     *              property="status_code",
     *              type="integer",
     *              example=200
     *          ),
     *          @OA\Property(
     *              property="message",
     *              type="string",
     *              example="User tags created successfully"
     *          ),
     *          @OA\Property(
     *              property="error",
     *              type="string",
     *              nullable=true
     *          ),
     *          @OA\Property(
     *              property="data",
     *              type="object",
     *              example={}
     *          )
     *      )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Something went wrong",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   ),
     *   @OA\Response(
     *     response=403,
     *     description="Exception Throwable",
     *     @OA\JsonContent(
     *          oneOf={@OA\Schema(ref="#/components/schemas/ExceptionRes")}
     *     )
     *   )
     * )
     */

    public function createUserTags(Request $request, Validate $validate)
    {

        $validationErrors = $validate->validate($request, $this->rules->createUserTagsValidationRules(), $this->validationMessages->createUserTagsValidationMessages());

        if ($validationErrors) {
            return (new ErrorResource($validationErrors))->response()->setStatusCode(400);
        }

        $userTags = $request->user_tags;
        $userID = $request->user()->id;
        try {
            foreach ($userTags as $tagId) {
                UserTag::updateOrCreate(
                    ['user_id' => $userID, 'tag_id' => $tagId], // Unique criteria
                    [] // No additional attributes to update (optional)
                );
            }
            $status = 200;
            $message = trans('message.user_tag.created');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        } catch (Exception $ex) {
            $status = 400;
            $message = trans('message.error.failed');
            return $this->resProvider->apiJsonResponse($status, $message, null, null);
        }
    }
}

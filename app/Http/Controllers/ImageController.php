<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageRequest;
use App\Http\Resources\ImageResource;
use App\Models\Page;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    /**
     * Get the images list filtered by the specific page
     * 
     * @param ImageRequest $request
     * @param Page $pageName
     * @return Response
     */
    public function pageImagesListing(ImageRequest $request) 
    {
        $pageName = $request->page_name;
        $images = [];

        try {
            $page = Page::where('name', $pageName)->first();
            if($page) {
                $images = ImageResource::collection($page->images);
            }
            return $this->resProvider->apiJsonResponse(200, config('message.success.success'), $images, '');
        } catch (\Throwable $e) {
            return $this->resProvider->apiJsonResponse(400, config('message.error.exception'), '', $e->getMessage());
        }
    }
}

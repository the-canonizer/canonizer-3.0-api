<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) :array
    {
        return [
            'title' => $this->ttitle,
            'description' => $this->description,
            'route' => $this->route,
            'image_url' => $this->url
        ];
    }
}

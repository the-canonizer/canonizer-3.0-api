<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "client_id" => $this->client_id,
            "page_name" => $this->page->name,
            "slot" => $this->slot,
            "format" => $this->format,
            "adtest" => $this->adtest == 0 ? 'off' : 'on', 
            "is_responsive" => $this->is_responsive == 0 ? false : true, 
            "status" => $this->status == 0 ? false : true
        ];
    }
}

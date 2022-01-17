<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "status_code" => $this->status_code ?? 200,
            "message"     => $this->message ?? 'Success',
            "error"       => $this->error ?? null,
            "data"        => $this->data ?? null
        ];
    }
}
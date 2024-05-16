<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    public function toArray($response)
    {
        return [
            "status_code" => $this->status_code ?? 400,
            "message"     => $this->message ?? 'Something went wrong',
            "error"       => $this->error ?? null,
            "data"        => $this->data ?? null
        ];
    }
}
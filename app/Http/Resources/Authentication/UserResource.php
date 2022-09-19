<?php

namespace App\Http\Resources\Authentication;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            "id"      => $this->id ?? '',
            "first_name"      => $this->first_name ?? '',
            "middle_name"     => $this->middle_name ?? '',
            "last_name"       => $this->last_name ?? '',
            "email"           => $this->email ?? '',
            "phone_number"    => $this->phone_number ?? '',
            "mobile_verified" => $this->mobile_verified ?? 0,
            "birthday"        => $this->birthday ?? null,
            "default_algo"    => $this->default_algo ?? null,
            "private_flags"   => $this->private_flags ?? null,
            "join_time"       => $this->join_time ?? time(),
            "is_admin"       => $this->is_admin ?? null,
        ];
    }
}
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this['id'],
            'first_name' => $this['first_name'],
            'last_name' => $this['last_name'],
            'email' => $this['email'],
            'phone' => $this['phone'],
            'country' => $this['country'],
            'state' => $this['state'],
            'city' => $this['city'],
            'address' => $this['address'],
            'postcode' => $this['postcode'],
            'active' => $this['active'] == 1,
            'email_verified' => $this['email_verified_at'] != null
        ];
    }
}

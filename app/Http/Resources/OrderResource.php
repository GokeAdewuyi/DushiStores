<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $items = [];
        foreach ($this->items as $item)
            $items = [
                new ProductResource($item->product),
                'quantity' => $item['quantity'],
                'purchase_price' => $item['price']
            ];

        return [
            'id' => $this['id'],
            'tracking_code' => $this['code'],
            'amount' => $this['amount'],
            'status' => $this['status'],
            'shipping_details' => [
                'first_name' => $this['first_name'],
                'last_name' => $this['last_name'],
                'email' => $this['email'],
                'phone' => $this['phone'],
                'country' => $this['country'],
                'state' => $this['state'],
                'city' => $this['city'],
                'address' => $this['address'],
                'additional_note' => $this['note']
            ],
            'shipping_fee' => $this['shipping'],
            'items' => $items
        ];
    }
}

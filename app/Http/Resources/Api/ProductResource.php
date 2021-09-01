<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            "id" => $this['id'],
            "code" => $this['code'],
            "name" => $this['name'],
            "slug" => $this['slug'],
            "description" => $this['description'],
            "price" => $this['price'],
            "discount" => $this['discount'],
            "discountedPrice" => $this->getDiscountedPrice(),
            "sku" => $this['sku'],
            "in_stock" => $this['in_stock'] == 1,
            "quantity" => $this['quantity'],
            "weight" => $this['weight'],
            "sold" => $this['sold'],
            "media" => MediaResource::collection($this->media),
            "categories" => CategoryResource::collection($this->categories),
            "subCategories" => SubCategoryResource::collection($this->subCategories),
        ];
    }
}

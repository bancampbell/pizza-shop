<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'product' => new ProductResource($item->product),
                        'quantity' => $item->quantity,
                    ];
                });
            }),
            'total' => $this->when(isset($this->total), function () {
                return $this->total;
            }),
        ];
    }
}

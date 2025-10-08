<?php

namespace App\Http\Resources\Warehousing;

use App\Models\Warehousing\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->ItemName,
            'quantity' => $this->QuantityOnHand,
            'unit' => $this->unit['unitCode'] == 'LTRS' ? 'L' : $this->unit['unitCode'],
        ];
    }
}

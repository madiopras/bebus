<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SpecialDayResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'special_day_id' => $this->special_day_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'description' => $this->description,
            'price_percentage' => $this->price_percentage,
            'is_increase' => $this->is_increase,
            'is_active' => $this->is_active,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

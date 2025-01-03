<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'route_id' => $this->route_id,
            'start_location_id' => $this->start_location_id,
            'end_location_id' => $this->end_location_id,
            'distance' => $this->distance,
            'price' => $this->price,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

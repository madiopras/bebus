<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BusResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'bus_number' => $this->bus_number,
            'type_bus' => $this->type_bus,
            'capacity' => $this->capacity,
            'bus_name' => $this->bus_name,
            'class_id' => $this->class_id,
            'description' => $this->description,
            'cargo' => $this->cargo,
            'is_active' => $this->is_active,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

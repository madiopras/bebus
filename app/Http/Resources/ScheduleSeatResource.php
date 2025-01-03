<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleSeatResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'schedule_seat_id' => $this->schedule_seat_id,
            'schedule_id' => $this->schedule_id,
            'seat_id' => $this->seat_id,
            'is_available' => $this->is_available,
            'description' => $this->description,
            'created_by_id' => $this->created_by_id,
            'updated_by_id' => $this->updated_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

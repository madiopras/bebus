<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchedulesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'location_id' => 'required|integer',
            'bus_id' => 'sometimes|required|integer',
            'supir_id' => 'sometimes|required|integer',
            'departure_time' => 'sometimes|required|date',
            'arrival_time' => 'sometimes|required|date',
            'description' => 'nullable|string|max:255'
        ];
    }
}

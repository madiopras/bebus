<?php

namespace App\Http\Requests\Schedules;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchedulesRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'location_id' => 'required|integer',
            'bus_id' => 'required|integer',
            'supir_id' => 'required|integer',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date',
            'description' => 'nullable|string|max:255'
        ];
    }
}

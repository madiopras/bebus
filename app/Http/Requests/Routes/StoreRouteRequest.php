<?php

namespace App\Http\Requests\Routes;

use Illuminate\Foundation\Http\FormRequest;

class StoreRouteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_location_id' => 'required|integer',
            'end_location_id' => 'required|integer',
            'distance' => 'required|numeric',
            'price' => 'required|numeric',
        ];
    }
}

<?php

namespace App\Http\Requests\Routes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRouteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_location_id' => 'sometimes|required|integer',
            'end_location_id' => 'sometimes|required|integer',
            'distance' => 'sometimes|required|numeric',
            'price' => 'sometimes|required|numeric',
        ];
    }
}

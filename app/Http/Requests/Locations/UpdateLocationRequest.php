<?php

namespace App\Http\Requests\Locations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'state' => 'sometimes|required|string|max:255',
            'place' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:255',
        ];
    }
}

<?php

namespace App\Http\Requests\Locations;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'place' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ];
    }
}

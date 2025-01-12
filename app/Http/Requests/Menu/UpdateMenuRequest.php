<?php

namespace App\Http\Requests\Menu;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'menu_name' => 'required|string|max:255',
            'model_type' => 'nullable|string|max:255',
            'description' => 'nullable|string'
        ];
    }
} 
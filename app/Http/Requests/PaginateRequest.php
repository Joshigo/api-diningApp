<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'hasEaten' => 'sometimes|boolean',
        ];
    }


    public function messages()
    {
        return [
            'hasEaten.boolean' => 'El parámetro hasEaten debe ser true o false.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor a 0.',
            'per_page.integer' => 'El número de elementos por página debe ser un número entero.',
            'per_page.min' => 'El número de elementos por página debe ser mayor a 0.',
            'per_page.max' => 'El número de elementos por página no puede ser mayor a 100.',
        ];
    }
}

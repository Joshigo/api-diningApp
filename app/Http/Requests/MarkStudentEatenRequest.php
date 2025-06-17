<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkStudentEatenRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'studient_id' => [
                'required',
                'integer',
                'exists:studients,id'
            ],
        ];
    }

    public function messages()
    {
        return [
            'studient_id.required' => 'El ID del estudiante es requerido.',
            'studient_id.integer' => 'El ID del estudiante debe ser un número entero.',
            'studient_id.exists' => 'El estudiante especificado no existe.',
        ];
    }

    public function attributes()
    {
        return [
            'studient_id' => 'ID del estudiante',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['sometimes', 'string', 'max:255'],
            'institution_name' => ['sometimes', 'string', 'max:255'],
            'city'             => ['sometimes', 'string', 'max:100'],
            'address'          => ['sometimes', 'nullable', 'string', 'max:500'],
            'phone'            => ['sometimes', 'nullable', 'string', 'max:20'],
        ];
    }
}

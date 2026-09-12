<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterHospitalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'institution_name' => ['required', 'string', 'max:255'],
            'city'             => ['required', 'string', 'max:100'],
            'address'          => ['nullable', 'string', 'max:500'],
            'phone'            => ['nullable', 'string', 'max:20'],
        ];
    }
}

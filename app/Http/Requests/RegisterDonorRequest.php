<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'email'              => ['required', 'email', 'unique:users,email'],
            'password'           => ['required', 'string', 'min:8', 'confirmed'],
            'blood_type'         => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'phone'              => ['required', 'string', 'max:20'],
            'city'               => ['required', 'string', 'max:100'],
            'last_donation_date' => ['nullable', 'date', 'before:today'],
        ];
    }
}

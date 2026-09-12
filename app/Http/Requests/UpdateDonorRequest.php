<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDonorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:255'],
            'phone'              => ['sometimes', 'string', 'max:20'],
            'city'               => ['sometimes', 'string', 'max:100'],
            'blood_type'         => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'last_donation_date' => ['sometimes', 'nullable', 'date', 'before:today'],
        ];
    }
}

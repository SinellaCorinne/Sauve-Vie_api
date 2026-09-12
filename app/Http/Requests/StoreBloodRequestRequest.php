<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBloodRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les hôpitaux peuvent créer une demande
        return $this->user()->isHospital();
    }

    public function rules(): array
    {
        return [
            'blood_type'     => ['required', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'urgency_level'  => ['sometimes', 'in:normal,urgent,critical'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'expires_at'     => ['nullable', 'date', 'after:now'],
        ];
    }
}

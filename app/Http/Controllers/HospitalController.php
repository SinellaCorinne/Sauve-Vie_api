<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHospitalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HospitalController extends Controller
{
    /**
     * GET /api/hospital/profile
     * Retourne le profil de l'hôpital authentifié (user + détails institution).
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isHospital()) {
            return response()->json(['message' => 'Accès réservé aux hôpitaux.'], 403);
        }

        $user->load('hospital');

        return response()->json($user);
    }

    /**
     * PATCH /api/hospital/profile
     * Mise à jour du profil hôpital (nom du compte + données institution).
     */
    public function update(UpdateHospitalRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isHospital()) {
            return response()->json(['message' => 'Accès réservé aux hôpitaux.'], 403);
        }

        $validated = $request->validated();

        // Mise à jour du compte utilisateur
        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        // Mise à jour du profil étendu de l'hôpital
        $hospitalFields = array_filter([
            'institution_name' => $validated['institution_name'] ?? null,
            'city'             => $validated['city'] ?? null,
            'address'          => $validated['address'] ?? null,
            'phone'            => $validated['phone'] ?? null,
        ], fn ($v) => ! is_null($v));

        if (! empty($hospitalFields)) {
            $user->hospital()->update($hospitalFields);
        }

        $user->load('hospital');

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user'    => $user->fresh(['hospital']),
        ]);
    }
}

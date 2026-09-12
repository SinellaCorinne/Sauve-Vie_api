<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDonorRequest;
use App\Models\DonorResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonorController extends Controller
{
    /**
     * GET /api/donor/profile
     * Retourne le profil complet du donneur authentifié.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json(['message' => 'Accès réservé aux donneurs.'], 403);
        }

        return response()->json($user);
    }

    /**
     * PATCH /api/donor/profile
     * Mise à jour du profil du donneur (nom, téléphone, ville, groupe, dernier don).
     */
    public function update(UpdateDonorRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json(['message' => 'Accès réservé aux donneurs.'], 403);
        }

        $user->update($request->validated());

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user'    => $user->fresh(),
        ]);
    }

    /**
     * PATCH /api/donor/availability
     * Bascule la disponibilité du donneur (on/off).
     * Permet au donneur de se rendre indisponible temporairement.
     */
    public function toggleAvailability(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json(['message' => 'Accès réservé aux donneurs.'], 403);
        }

        $user->update(['is_available' => ! $user->is_available]);

        $status = $user->is_available ? 'disponible' : 'indisponible';

        return response()->json([
            'message'      => "Vous êtes maintenant {$status}.",
            'is_available' => $user->is_available,
        ]);
    }

    /**
     * GET /api/donor/history
     * Historique des réponses/dons du donneur authentifié.
     * Inclut la demande associée pour avoir le contexte (groupe, hôpital, date).
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json(['message' => 'Accès réservé aux donneurs.'], 403);
        }

        $history = DonorResponse::with(['bloodRequest.hospitalProfile'])
            ->where('donor_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json($history);
    }
}

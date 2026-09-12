<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use App\Models\DonorResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DonorResponseController extends Controller
{
    /**
     * POST /api/blood-requests/{bloodRequest}/respond
     * Un donneur signale sa disponibilité pour une demande.
     *
     * Vérifications :
     * - L'utilisateur est bien un donneur
     * - La demande est ouverte
     * - Le groupe sanguin est compatible
     * - Le donneur n'a pas donné il y a moins de 3 mois
     * - Le donneur n'a pas déjà répondu à cette demande
     */
    public function respond(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json(['message' => 'Accès réservé aux donneurs.'], 403);
        }

        if (! $bloodRequest->isOpen()) {
            return response()->json(['message' => 'Cette demande est clôturée.'], 422);
        }

        // Vérification compatibilité groupe sanguin
        $compatible = BloodRequest::compatibleDonorTypes($bloodRequest->blood_type);
        if (! in_array($user->blood_type, $compatible)) {
            return response()->json([
                'message'          => 'Votre groupe sanguin n\'est pas compatible avec cette demande.',
                'your_blood_type'  => $user->blood_type,
                'required_compatible' => $compatible,
            ], 422);
        }

        // Vérification délai de 3 mois depuis le dernier don
        if ($user->last_donation_date) {
            $threeMonthsAgo = Carbon::now()->subMonths(3);
            if ($user->last_donation_date->greaterThan($threeMonthsAgo)) {
                $nextEligibleDate = $user->last_donation_date->copy()->addMonths(3)->format('d/m/Y');
                return response()->json([
                    'message'            => "Vous ne pouvez pas donner avant le {$nextEligibleDate} (délai de 3 mois entre deux dons).",
                    'next_eligible_date' => $nextEligibleDate,
                ], 422);
            }
        }

        // Vérification unicité (contrainte DB + vérification applicative)
        $existing = DonorResponse::where('donor_id', $user->id)
            ->where('blood_request_id', $bloodRequest->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message'  => 'Vous avez déjà répondu à cette demande.',
                'response' => $existing,
            ], 409);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $response = DonorResponse::create([
            'donor_id'         => $user->id,
            'blood_request_id' => $bloodRequest->id,
            'status'           => 'pending',
            'note'             => $validated['note'] ?? null,
        ]);

        $response->load('bloodRequest');

        return response()->json([
            'message'  => 'Votre disponibilité a été enregistrée. L\'hôpital vous contactera.',
            'response' => $response,
        ], 201);
    }

    /**
     * GET /api/blood-requests/{bloodRequest}/responses
     * Liste des réponses de donneurs pour une demande (hôpital propriétaire uniquement).
     */
    public function index(BloodRequest $bloodRequest, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isHospital() || $bloodRequest->hospital_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        $responses = DonorResponse::with(['donor:id,name,blood_type,phone,city,last_donation_date'])
            ->where('blood_request_id', $bloodRequest->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'blood_request_id' => $bloodRequest->id,
            'total'            => $responses->count(),
            'responses'        => $responses,
        ]);
    }

    /**
     * PATCH /api/blood-requests/{bloodRequest}/responses/{donorResponse}
     * L'hôpital confirme ou décline la réponse d'un donneur.
     */
    public function updateStatus(
        BloodRequest   $bloodRequest,
        DonorResponse  $donorResponse,
        Request        $request
    ): JsonResponse {
        $user = $request->user();

        if (! $user->isHospital() || $bloodRequest->hospital_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé.'], 403);
        }

        if ($donorResponse->blood_request_id !== $bloodRequest->id) {
            return response()->json(['message' => 'Cette réponse n\'appartient pas à cette demande.'], 422);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,declined'],
        ]);

        $donorResponse->update(['status' => $validated['status']]);

        // Si le don est confirmé, on met à jour la date du dernier don du donneur
        if ($validated['status'] === 'confirmed') {
            $donorResponse->donor()->update([
                'last_donation_date' => now()->toDateString(),
            ]);
        }

        return response()->json([
            'message'  => 'Statut mis à jour.',
            'response' => $donorResponse->fresh('donor'),
        ]);
    }

    /**
     * DELETE /api/blood-requests/{bloodRequest}/respond
     * Un donneur annule sa réponse (tant qu'elle est en statut pending).
     */
    public function cancel(BloodRequest $bloodRequest, Request $request): JsonResponse
    {
        $user = $request->user();

        $response = DonorResponse::where('donor_id', $user->id)
            ->where('blood_request_id', $bloodRequest->id)
            ->first();

        if (! $response) {
            return response()->json(['message' => 'Aucune réponse trouvée.'], 404);
        }

        if (! $response->isPending()) {
            return response()->json([
                'message' => 'Impossible d\'annuler une réponse déjà traitée.',
                'status'  => $response->status,
            ], 422);
        }

        $response->delete();

        return response()->json(['message' => 'Votre réponse a été annulée.']);
    }
}

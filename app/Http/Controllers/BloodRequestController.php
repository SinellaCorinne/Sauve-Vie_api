<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBloodRequestRequest;
use App\Models\BloodRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloodRequestController extends Controller
{
    /**
     * GET /api/blood-requests
     * Liste publique des demandes ouvertes (visible par tous les utilisateurs auth).
     * Filtres optionnels : blood_type, city, urgency_level.
     */
    public function index(Request $request): JsonResponse
    {
        $query = BloodRequest::with('hospitalProfile')
            ->active()
            ->orderByRaw("CASE urgency_level WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc');

        // Filtre par groupe sanguin (ex: ?blood_type=O-)
        if ($request->filled('blood_type')) {
            $query->where('blood_type', $request->blood_type);
        }

        // Filtre par ville
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        // Filtre par niveau d'urgence
        if ($request->filled('urgency_level')) {
            $query->where('urgency_level', $request->urgency_level);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * GET /api/blood-requests/{id}
     * Détail d'une demande avec le nombre de réponses.
     */
    public function show(BloodRequest $bloodRequest): JsonResponse
    {
        $bloodRequest->load(['hospitalProfile', 'donorResponses']);
        $bloodRequest->loadCount(['donorResponses', 'confirmedDonors']);

        return response()->json($bloodRequest);
    }

    /**
     * POST /api/blood-requests
     * Création d'une demande urgente par l'hôpital authentifié.
     * La ville est automatiquement dénormalisée depuis le profil hôpital.
     */
    public function store(StoreBloodRequestRequest $request): JsonResponse
    {
        $user = $request->user();

        // Récupération de la ville depuis le profil hôpital (dénormalisation)
        $city = $user->hospital?->city;

        $bloodRequest = BloodRequest::create([
            'hospital_id'   => $user->id,
            'blood_type'    => $request->blood_type,
            'urgency_level' => $request->urgency_level ?? 'urgent',
            'description'   => $request->description,
            'city'          => $city,
            'status'        => 'open',
            'expires_at'    => $request->expires_at,
        ]);

        $bloodRequest->load('hospitalProfile');

        return response()->json([
            'message'      => 'Demande créée avec succès.',
            'blood_request' => $bloodRequest,
        ], 201);
    }

    /**
     * PATCH /api/blood-requests/{id}
     * Mise à jour d'une demande ouverte (uniquement par l'hôpital propriétaire).
     */
    public function update(Request $request, BloodRequest $bloodRequest): JsonResponse
    {
        $this->authorize('update', $bloodRequest);

        if (! $bloodRequest->isOpen()) {
            return response()->json(['message' => 'Impossible de modifier une demande clôturée.'], 422);
        }

        $validated = $request->validate([
            'blood_type'    => ['sometimes', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'urgency_level' => ['sometimes', 'in:normal,urgent,critical'],
            'description'   => ['sometimes', 'nullable', 'string', 'max:1000'],
            'expires_at'    => ['sometimes', 'nullable', 'date', 'after:now'],
        ]);

        $bloodRequest->update($validated);

        return response()->json([
            'message'       => 'Demande mise à jour.',
            'blood_request' => $bloodRequest->fresh('hospitalProfile'),
        ]);
    }

    /**
     * PATCH /api/blood-requests/{id}/fulfill
     * Clôturer une demande comme satisfaite (hôpital propriétaire uniquement).
     */
    public function fulfill(BloodRequest $bloodRequest): JsonResponse
    {
        $this->authorize('fulfill', $bloodRequest);

        if (! $bloodRequest->isOpen()) {
            return response()->json([
                'message' => 'Cette demande est déjà clôturée.',
                'status'  => $bloodRequest->status,
            ], 422);
        }

        $bloodRequest->update(['status' => 'fulfilled']);

        return response()->json([
            'message'       => 'Demande marquée comme satisfaite.',
            'blood_request' => $bloodRequest,
        ]);
    }

    /**
     * PATCH /api/blood-requests/{id}/expire
     * Marquer une demande comme expirée (hôpital propriétaire uniquement).
     */
    public function expire(BloodRequest $bloodRequest): JsonResponse
    {
        $this->authorize('expire', $bloodRequest);

        if (! $bloodRequest->isOpen()) {
            return response()->json([
                'message' => 'Cette demande est déjà clôturée.',
                'status'  => $bloodRequest->status,
            ], 422);
        }

        $bloodRequest->update(['status' => 'expired']);

        return response()->json([
            'message'       => 'Demande marquée comme expirée.',
            'blood_request' => $bloodRequest,
        ]);
    }

    /**
     * DELETE /api/blood-requests/{id}
     * Suppression d'une demande (hôpital propriétaire, uniquement si ouverte).
     */
    public function destroy(BloodRequest $bloodRequest): JsonResponse
    {
        $this->authorize('delete', $bloodRequest);

        if (! $bloodRequest->isOpen()) {
            return response()->json(['message' => 'Impossible de supprimer une demande clôturée.'], 422);
        }

        $bloodRequest->delete();

        return response()->json(['message' => 'Demande supprimée.'], 200);
    }

    /**
     * GET /api/hospital/blood-requests
     * Liste des demandes de l'hôpital authentifié (tous statuts), avec comptage des réponses.
     */
    public function myRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isHospital()) {
            return response()->json(['message' => 'Accès réservé aux hôpitaux.'], 403);
        }

        $requests = BloodRequest::withCount(['donorResponses', 'confirmedDonors'])
            ->where('hospital_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($requests);
    }
}

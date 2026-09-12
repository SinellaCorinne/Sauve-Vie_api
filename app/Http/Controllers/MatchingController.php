<?php

namespace App\Http\Controllers;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchingController extends Controller
{
    /**
     * GET /api/blood-requests/{id}/matching
     * Retourne les donneurs compatibles et éligibles pour une demande donnée.
     *
     * Règles appliquées :
     * 1. Le groupe sanguin du donneur doit être compatible (matrice ABO+Rh)
     * 2. Le donneur doit être marqué disponible (is_available = true)
     * 3. Le donneur ne doit pas avoir donné il y a moins de 3 mois
     *
     * Accessible uniquement par l'hôpital propriétaire de la demande.
     */
    public function matchDonors(BloodRequest $bloodRequest, Request $request): JsonResponse
    {
        $this->authorize('viewMatching', $bloodRequest);

        if (! $bloodRequest->isOpen()) {
            return response()->json([
                'message' => 'Cette demande est clôturée, le matching n\'est plus disponible.',
                'status'  => $bloodRequest->status,
            ], 422);
        }

        // Récupération des IDs des donneurs ayant déjà répondu
        // (pour pouvoir les marquer dans les résultats)
        $respondedDonorIds = $bloodRequest->donorResponses()
            ->pluck('donor_id')
            ->toArray();

        // Filtres optionnels depuis la requête
        $city = $request->query('city'); // ?city=Alger

        $donors = User::eligible()
            ->compatibleWith($bloodRequest->blood_type)
            ->when($city, fn ($q) => $q->where('city', 'like', '%' . $city . '%'))
            ->select(['id', 'name', 'blood_type', 'city', 'phone', 'last_donation_date', 'is_available'])
            ->orderBy('last_donation_date', 'asc') // les plus anciens donneurs en premier
            ->paginate(30)
            ->through(function ($donor) use ($respondedDonorIds) {
                // Indique si le donneur a déjà répondu à cette demande
                $donor->has_responded = in_array($donor->id, $respondedDonorIds);
                return $donor;
            });

        return response()->json([
            'blood_request'   => [
                'id'            => $bloodRequest->id,
                'blood_type'    => $bloodRequest->blood_type,
                'urgency_level' => $bloodRequest->urgency_level,
                'city'          => $bloodRequest->city,
            ],
            'compatible_types' => BloodRequest::compatibleDonorTypes($bloodRequest->blood_type),
            'donors'           => $donors,
        ]);
    }

    /**
     * GET /api/donor/compatible-requests
     * Retourne les demandes ouvertes compatibles avec le groupe sanguin du donneur connecté.
     * Accessible uniquement par un donneur.
     */
    public function compatibleRequests(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->isDonor()) {
            return response()->json(['message' => 'Accès réservé aux donneurs.'], 403);
        }

        if (! $user->blood_type) {
            return response()->json([
                'message' => 'Veuillez renseigner votre groupe sanguin dans votre profil.',
            ], 422);
        }

        // Un donneur de groupe X peut donner aux groupes qui acceptent X.
        // On cherche les demandes dont le groupe recherché accepte le groupe du donneur.
        $donorType = $user->blood_type;

        // On cherche toutes les demandes ouvertes dont le groupe requiert ce donneur
        $requests = BloodRequest::with('hospitalProfile')
            ->active()
            ->whereIn('blood_type', function ($query) use ($donorType) {
                // On filtre les types de receveurs pour lesquels ce donneur est compatible
                $compatibleReceiverTypes = $this->getCompatibleReceiverTypes($donorType);
                $query->selectRaw('unnest(ARRAY[' .
                    implode(',', array_fill(0, count($compatibleReceiverTypes), '?')) .
                    ']::text[])', $compatibleReceiverTypes);
            })
            ->orderByRaw("CASE urgency_level WHEN 'critical' THEN 0 WHEN 'urgent' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'donor_blood_type' => $donorType,
            'requests'         => $requests,
        ]);
    }

    /**
     * Retourne les groupes sanguins de receveurs pour lesquels ce donneur est compatible.
     * C'est l'inverse de compatibleDonorTypes : "à qui puis-je donner ?"
     *
     * @param  string $donorType  Groupe sanguin du donneur
     * @return array<string>
     */
    private function getCompatibleReceiverTypes(string $donorType): array
    {
        $allTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $compatible = [];

        foreach ($allTypes as $receiverType) {
            if (in_array($donorType, BloodRequest::compatibleDonorTypes($receiverType))) {
                $compatible[] = $receiverType;
            }
        }

        return $compatible;
    }
}

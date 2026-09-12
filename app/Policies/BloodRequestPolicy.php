<?php

namespace App\Policies;

use App\Models\BloodRequest;
use App\Models\User;

class BloodRequestPolicy
{
    /**
     * Seuls les hôpitaux peuvent voir le matching de leurs propres demandes.
     */
    public function viewMatching(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->isHospital() && $bloodRequest->hospital_id === $user->id;
    }

    /**
     * Seul l'hôpital propriétaire peut modifier une demande.
     */
    public function update(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->isHospital() && $bloodRequest->hospital_id === $user->id;
    }

    /**
     * Seul l'hôpital propriétaire peut clôturer une demande comme satisfaite.
     */
    public function fulfill(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->isHospital() && $bloodRequest->hospital_id === $user->id;
    }

    /**
     * Seul l'hôpital propriétaire peut marquer une demande comme expirée.
     */
    public function expire(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->isHospital() && $bloodRequest->hospital_id === $user->id;
    }

    /**
     * Seul l'hôpital propriétaire peut supprimer une demande.
     */
    public function delete(User $user, BloodRequest $bloodRequest): bool
    {
        return $user->isHospital() && $bloodRequest->hospital_id === $user->id;
    }
}

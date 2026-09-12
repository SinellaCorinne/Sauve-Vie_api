<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BloodRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'blood_type',
        'urgency_level',
        'description',
        'city',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // ── Matrice de compatibilité ABO + Rhésus ────────────────────────────────
    //
    // Règle : un donneur de groupe X peut donner à un receveur de groupe Y
    // si X est dans la liste des donneurs compatibles de Y.
    // Source : règles transfusionnelles standard.
    //
    // La clé est le groupe RECHERCHÉ (le receveur).
    // La valeur est la liste des groupes DONNEURS acceptés.

    private static array $compatibility = [
        'A+'  => ['A+', 'A-', 'O+', 'O-'],
        'A-'  => ['A-', 'O-'],
        'B+'  => ['B+', 'B-', 'O+', 'O-'],
        'B-'  => ['B-', 'O-'],
        'AB+' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], // receveur universel
        'AB-' => ['A-', 'B-', 'AB-', 'O-'],
        'O+'  => ['O+', 'O-'],
        'O-'  => ['O-'],  // donneur universel, ne peut recevoir que O-
    ];

    /**
     * Retourne les groupes sanguins de donneurs compatibles avec le groupe recherché.
     *
     * @param  string $requestedType  Groupe sanguin du receveur (ex: 'AB+')
     * @return array<string>          Liste des groupes sanguins compatibles
     */
    public static function compatibleDonorTypes(string $requestedType): array
    {
        return self::$compatibility[$requestedType] ?? [];
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    /** Uniquement les demandes ouvertes */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /** Demandes non expirées (expires_at null ou dans le futur) */
    public function scopeActive($query)
    {
        return $query->where('status', 'open')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    // ── Relations ───────────────────────────────────────────────────────────

    /** L'hôpital (User) qui a publié la demande */
    public function hospital(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }

    /** Profil étendu de l'hôpital */
    public function hospitalProfile(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'user_id');
    }

    /** Réponses des donneurs à cette demande */
    public function donorResponses(): HasMany
    {
        return $this->hasMany(DonorResponse::class);
    }

    /** Donneurs ayant confirmé leur don pour cette demande */
    public function confirmedDonors(): HasMany
    {
        return $this->hasMany(DonorResponse::class)->where('status', 'confirmed');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isFulfilled(): bool
    {
        return $this->status === 'fulfilled';
    }
}

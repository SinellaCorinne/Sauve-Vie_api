<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'blood_type',
        'phone',
        'city',
        'is_available',
        'last_donation_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_available'      => 'boolean',
            'last_donation_date' => 'date',
        ];
    }

    // ── Helpers de rôle ─────────────────────────────────────────────────────

    public function isDonor(): bool
    {
        return $this->role === 'donor';
    }

    public function isHospital(): bool
    {
        return $this->role === 'hospital';
    }

    // ── Relations ───────────────────────────────────────────────────────────

    /** Profil étendu de l'hôpital (1-1) */
    public function hospital(): HasOne
    {
        return $this->hasOne(Hospital::class);
    }

    /** Demandes publiées par cet hôpital */
    public function bloodRequests(): HasMany
    {
        return $this->hasMany(BloodRequest::class, 'hospital_id');
    }

    /** Réponses de ce donneur à des demandes */
    public function donorResponses(): HasMany
    {
        return $this->hasMany(DonorResponse::class, 'donor_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────────────

    /** Scope : uniquement les donneurs */
    public function scopeDonors($query)
    {
        return $query->where('role', 'donor');
    }

    /** Scope : donneurs disponibles et n'ayant pas donné depuis moins de 3 mois */
    public function scopeEligible($query)
    {
        $threeMonthsAgo = Carbon::now()->subMonths(3)->toDateString();

        return $query->where('role', 'donor')
            ->where('is_available', true)
            ->where(function ($q) use ($threeMonthsAgo) {
                $q->whereNull('last_donation_date')
                  ->orWhere('last_donation_date', '<=', $threeMonthsAgo);
            });
    }

    /**
     * Scope : donneurs compatibles avec un groupe sanguin donné.
     * La compatibilité est gérée par la méthode statique BloodRequest::compatibleDonorTypes().
     */
    public function scopeCompatibleWith($query, string $bloodType)
    {
        $compatible = BloodRequest::compatibleDonorTypes($bloodType);

        return $query->whereIn('blood_type', $compatible);
    }
}

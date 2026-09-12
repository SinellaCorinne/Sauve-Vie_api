<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hospital extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'institution_name',
        'address',
        'city',
        'phone',
    ];

    // ── Relations ───────────────────────────────────────────────────────────

    /** Compte utilisateur associé à cet hôpital */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Demandes de sang publiées par cet hôpital (via le user) */
    public function bloodRequests(): HasMany
    {
        return $this->hasMany(BloodRequest::class, 'hospital_id', 'user_id');
    }
}

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\DonorResponseController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\MatchingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Endpoint de diagnostic temporaire (à supprimer après fix) ────────────────
Route::post('/debug-input', function (Request $request) {
    return response()->json([
        'all'          => $request->all(),
        'raw_content'  => $request->getContent(),
        'content_type' => $request->header('Content-Type'),
        'accept'       => $request->header('Accept'),
        'method'       => $request->method(),
        'php_input'    => file_get_contents('php://input'),
    ]);
});

/*
|--------------------------------------------------------------------------
| API Routes — Blood Donor Platform
|--------------------------------------------------------------------------
|
| Prefix automatique : /api  (défini dans bootstrap/app.php)
|
| Authentification : Laravel Sanctum (token Bearer)
|
| Deux rôles :
|   - donor   : donneur de sang
|   - hospital : hôpital / centre de santé
|
*/

// ── Auth (public) ────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('register/donor',    [AuthController::class, 'registerDonor']);
    Route::post('register/hospital', [AuthController::class, 'registerHospital']);
    Route::post('login',             [AuthController::class, 'login']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });
});

// ── Routes protégées (Sanctum) ───────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // ── Donneur ──────────────────────────────────────────────────────────────
    Route::prefix('donor')->group(function () {
        Route::get('profile',                   [DonorController::class, 'profile']);
        Route::patch('profile',                 [DonorController::class, 'update']);
        Route::patch('availability',            [DonorController::class, 'toggleAvailability']);
        Route::get('history',                   [DonorController::class, 'history']);

        // Demandes ouvertes compatibles avec le groupe sanguin du donneur connecté
        Route::get('compatible-requests',       [MatchingController::class, 'compatibleRequests']);
    });

    // ── Hôpital ──────────────────────────────────────────────────────────────
    Route::prefix('hospital')->group(function () {
        Route::get('profile',                   [HospitalController::class, 'profile']);
        Route::patch('profile',                 [HospitalController::class, 'update']);

        // Toutes les demandes de l'hôpital connecté (tous statuts)
        Route::get('blood-requests',            [BloodRequestController::class, 'myRequests']);
    });

    // ── Demandes de sang ──────────────────────────────────────────────────────
    Route::prefix('blood-requests')->group(function () {
        // Liste publique (auth) + création
        Route::get('/',                         [BloodRequestController::class, 'index']);
        Route::post('/',                        [BloodRequestController::class, 'store']);

        Route::prefix('{bloodRequest}')->group(function () {
            Route::get('/',                     [BloodRequestController::class, 'show']);
            Route::patch('/',                   [BloodRequestController::class, 'update']);
            Route::delete('/',                  [BloodRequestController::class, 'destroy']);

            // Cycle de vie de la demande
            Route::patch('fulfill',             [BloodRequestController::class, 'fulfill']);
            Route::patch('expire',              [BloodRequestController::class, 'expire']);

            // Matching : donneurs compatibles (hôpital propriétaire)
            Route::get('matching',              [MatchingController::class, 'matchDonors']);

            // Réponses des donneurs
            Route::post('respond',              [DonorResponseController::class, 'respond']);
            Route::delete('respond',            [DonorResponseController::class, 'cancel']);
            Route::get('responses',             [DonorResponseController::class, 'index']);
            Route::patch('responses/{donorResponse}', [DonorResponseController::class, 'updateStatus']);
        });
    });
});

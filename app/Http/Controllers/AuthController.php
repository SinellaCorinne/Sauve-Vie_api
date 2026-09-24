<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterDonorRequest;
use App\Http\Requests\RegisterHospitalRequest;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register/donor
     * Inscription d'un nouveau donneur.
     */
    public function registerDonor(RegisterDonorRequest $request): JsonResponse
    {
        \Log::info('RegisterDonor request payload', [
            'body' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $user = User::create([
            'name'               => $request->name,
            'email'              => $request->email,
            'password'           => $request->password, // hashé automatiquement via cast
            'role'               => 'donor',
            'blood_type'         => $request->blood_type,
            'phone'              => $request->phone,
            'city'               => $request->city,
            'is_available'       => true,
            'last_donation_date' => $request->last_donation_date,
        ]);

        $token = $user->createToken('donor-token')->plainTextToken;

        return response()->json([
            'message' => 'Inscription réussie.',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    /**
     * POST /api/auth/register/hospital
     * Inscription d'un hôpital (compte + profil étendu).
     */
    public function registerHospital(RegisterHospitalRequest $request): JsonResponse
    {
        \Log::info('RegisterHospital request payload', [
            'body' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => 'hospital',
        ]);

        // Création du profil étendu dans la table hospitals
        $hospital = Hospital::create([
            'user_id'          => $user->id,
            'institution_name' => $request->institution_name,
            'city'             => $request->city,
            'address'          => $request->address,
            'phone'            => $request->phone,
        ]);

        $token = $user->createToken('hospital-token')->plainTextToken;

        return response()->json([
            'message'  => 'Inscription réussie.',
            'user'     => $user,
            'hospital' => $hospital,
            'token'    => $token,
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Connexion unifiée donneur + hôpital.
     */
    public function login(Request $request): JsonResponse
    {
        \Log::info('AuthController@login payload', [
            'body' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            \Log::warning('AuthController@login failed: invalid credentials', [
                'email' => $request->email,
            ]);
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Révocation des anciens tokens pour éviter leur accumulation
        $user->tokens()->delete();

        $tokenName = $user->isDonor() ? 'donor-token' : 'hospital-token';
        $token     = $user->createToken($tokenName)->plainTextToken;

        // Charge le profil hôpital si besoin
        if ($user->isHospital()) {
            $user->load('hospital');
        }

        return response()->json([
            'message' => 'Connexion réussie.',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    /**
     * POST /api/auth/logout
     * Révocation du token courant.
     */
    public function logout(Request $request): JsonResponse
    {
        \Log::info('AuthController@logout', [
            'user_id' => $request->user()?->id,
            'token_present' => (bool) $request->bearerToken(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    /**
     * GET /api/auth/me
     * Retourne l'utilisateur authentifié avec son profil.
     */
    public function me(Request $request): JsonResponse
    {
        \Log::info('AuthController@me', [
            'user_id' => $request->user()?->id,
            'has_token' => (bool) $request->bearerToken(),
        ]);

        $user = $request->user();

        if ($user->isHospital()) {
            $user->load('hospital');
        }

        return response()->json($user);
    }
}

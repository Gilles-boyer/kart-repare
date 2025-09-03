<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Login user and create token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ], [
                'email.required' => 'L\'adresse email est obligatoire.',
                'email.email' => 'L\'adresse email doit être valide.',
                'password.required' => 'Le mot de passe est obligatoire.',
                'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données de connexion invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Identifiants incorrects'
                ], 401);
            }

            $user = Auth::user();

            // Vérifier si le compte est actif
            if (!$user->is_active) {
                Auth::logout();
                return response()->json([
                    'message' => 'Votre compte est désactivé. Contactez l\'administrateur.'
                ], 403);
            }

            // Mettre à jour la dernière connexion
            $user->update(['last_login_at' => now()]);

            // Créer le token avec les capacités basées sur le rôle
            $abilities = $this->getUserAbilities($user);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            return response()->json([
                'message' => 'Connexion réussie',
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la connexion',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:1000',
                'company' => 'nullable|string|max:255',
            ], [
                'first_name.required' => 'Le prénom est obligatoire.',
                'last_name.required' => 'Le nom est obligatoire.',
                'email.required' => 'L\'adresse email est obligatoire.',
                'email.unique' => 'Cette adresse email est déjà utilisée.',
                'password.required' => 'Le mot de passe est obligatoire.',
                'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
                'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données d\'inscription invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $userData = $request->all();
            $userData['password'] = Hash::make($userData['password']);
            $userData['role'] = 'client'; // Par défaut, les nouveaux utilisateurs sont des clients

            $user = User::create($userData);

            // Créer le token
            $abilities = $this->getUserAbilities($user);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            return response()->json([
                'message' => 'Inscription réussie',
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'inscription',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'message' => 'Profil récupéré avec succès',
                'user' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération du profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Logout the user and revoke token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Révoquer le token actuel
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la déconnexion',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Refresh the user's token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Révoquer le token actuel
            $request->user()->currentAccessToken()->delete();

            // Créer un nouveau token
            $abilities = $this->getUserAbilities($user);
            $token = $user->createToken('auth-token', $abilities)->plainTextToken;

            return response()->json([
                'message' => 'Token rafraîchi avec succès',
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du rafraîchissement du token',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Get user abilities based on role.
     *
     * @param User $user
     * @return array
     */
    private function getUserAbilities(User $user): array
    {
        return match ($user->role) {
            'admin' => ['*'], // Toutes les permissions
            'bureau_staff' => [
                'users:view',
                'users:create',
                'users:update',
                'repairs:view',
                'repairs:create',
                'repairs:update',
                'invoices:view',
                'invoices:create',
                'invoices:update',
                'parts:view',
                'parts:create',
                'parts:update',
            ],
            'mechanic' => [
                'users:view-own',
                'repairs:view',
                'repairs:update',
                'parts:view',
                'parts:consume',
            ],
            'client' => [
                'users:view-own',
                'repairs:view-own',
                'invoices:view-own',
            ],
            default => ['users:view-own'],
        };
    }
}

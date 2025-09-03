<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        try {
            $query = User::query();

            // Filtres
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('company', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if (in_array($sortBy, ['first_name', 'last_name', 'email', 'role', 'created_at', 'last_login_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return UserResource::collection($users);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des utilisateurs',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userData = $request->validated();
            $userData['password'] = Hash::make($userData['password']);

            $user = User::create($userData);

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'data' => new UserResource($user)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        try {
            return response()->json([
                'message' => 'Utilisateur récupéré avec succès',
                'data' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userData = $request->validated();

            // Hasher le mot de passe s'il est fourni
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user->update($userData);

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur modifié avec succès',
                'data' => new UserResource($user->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la modification de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Get user profile (current authenticated user).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'message' => 'Profil récupéré avec succès',
                'data' => new UserResource($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération du profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Update user profile (current authenticated user).
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            DB::beginTransaction();

            $userData = $request->validated();

            // Hasher le mot de passe s'il est fourni
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user->update($userData);

            DB::commit();

            return response()->json([
                'message' => 'Profil modifié avec succès',
                'data' => new UserResource($user->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la modification du profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Toggle user active status.
     *
     * @param User $user
     * @return JsonResponse
     */
    public function toggleStatus(User $user): JsonResponse
    {
        $this->authorize('toggleStatus', $user);

        try {
            DB::beginTransaction();

            $user->update(['is_active' => !$user->is_active]);

            DB::commit();

            $status = $user->is_active ? 'activé' : 'désactivé';

            return response()->json([
                'message' => "Utilisateur {$status} avec succès",
                'data' => new UserResource($user->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors du changement de statut',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Get users statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            if (!$user->isAdmin() && !$user->isBureauStaff()) {
                return response()->json([
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'deleted_users' => User::onlyTrashed()->count(),
                'users_by_role' => [
                    'clients' => User::where('role', 'client')->count(),
                    'bureau_staff' => User::where('role', 'bureau_staff')->count(),
                    'mechanics' => User::where('role', 'mechanic')->count(),
                    'admins' => User::where('role', 'admin')->count(),
                ],
                'deleted_users_by_role' => [
                    'clients' => User::onlyTrashed()->where('role', 'client')->count(),
                    'bureau_staff' => User::onlyTrashed()->where('role', 'bureau_staff')->count(),
                    'mechanics' => User::onlyTrashed()->where('role', 'mechanic')->count(),
                    'admins' => User::onlyTrashed()->where('role', 'admin')->count(),
                ],
                'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
                'recent_logins' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
                'recent_deletions' => User::onlyTrashed()->where('deleted_at', '>=', now()->subDays(30))->count(),
            ];

            return response()->json([
                'message' => 'Statistiques récupérées avec succès',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Get list of soft deleted users.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function trashed(Request $request)
    {
        $this->authorize('viewTrashed', User::class);

        try {
            $query = User::onlyTrashed();

            // Filtres
            if ($request->filled('role')) {
                $query->where('role', $request->role);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('company', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'deleted_at');
            $sortOrder = $request->get('sort_order', 'desc');

            if (in_array($sortBy, ['first_name', 'last_name', 'email', 'role', 'deleted_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return UserResource::collection($users);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des utilisateurs supprimés',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Restore a soft deleted user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('restoreUser', $user);

        try {
            DB::beginTransaction();

            $user->restore();

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur restauré avec succès',
                'data' => new UserResource($user->fresh())
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la restauration de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }

    /**
     * Force delete a soft deleted user (permanent deletion).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $user);

        try {
            DB::beginTransaction();

            $user->forceDelete();

            DB::commit();

            return response()->json([
                'message' => 'Utilisateur supprimé définitivement avec succès'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la suppression définitive de l\'utilisateur',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur serveur'
            ], 500);
        }
    }
}

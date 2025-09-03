<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePilotRequest;
use App\Http\Requests\UpdatePilotRequest;
use App\Http\Resources\PilotResource;
use App\Models\Pilot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class PilotController extends Controller
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
        $this->authorize('viewAny', Pilot::class);

        try {
            $query = Pilot::with('client');

            /** @var User $user */
            $user = Auth::user();

            // Filtres par client (pour les clients qui ne voient que leurs pilotes)
            if ($user->isClient()) {
                $query->where('client_id', $user->id);
            } elseif ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            // Filtre par âge
            if ($request->filled('is_minor')) {
                $query->where('is_minor', $request->boolean('is_minor'));
            }

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'last_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $pilots = $query->paginate($perPage);

            return PilotResource::collection($pilots);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des pilotes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePilotRequest $request
     * @return JsonResponse|PilotResource
     */
    public function store(StorePilotRequest $request)
    {
        $this->authorize('create', Pilot::class);
        $this->authorize('createForClient', [Pilot::class, $request->client_id]);

        try {
            $pilot = Pilot::create($request->validated());
            $pilot->load('client');

            return new PilotResource($pilot);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du pilote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Pilot $pilot
     * @return JsonResponse|PilotResource
     */
    public function show(Pilot $pilot)
    {
        $this->authorize('view', $pilot);

        try {
            $pilot->load('client');
            return new PilotResource($pilot);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération du pilote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePilotRequest $request
     * @param Pilot $pilot
     * @return JsonResponse|PilotResource
     */
    public function update(UpdatePilotRequest $request, Pilot $pilot)
    {
        $this->authorize('update', $pilot);

        try {
            // Vérifier l'autorisation pour changer de client si nécessaire
            if ($request->filled('client_id') && $request->client_id != $pilot->client_id) {
                $this->authorize('createForClient', [Pilot::class, $request->client_id]);
            }

            $pilot->update($request->validated());
            $pilot->load('client');

            return new PilotResource($pilot);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du pilote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     *
     * @param Pilot $pilot
     * @return JsonResponse
     */
    public function destroy(Pilot $pilot)
    {
        $this->authorize('delete', $pilot);

        try {
            $pilot->delete();

            return response()->json([
                'message' => 'Pilote supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du pilote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of trashed pilots.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function trashed(Request $request)
    {
        $this->authorize('viewTrashed', Pilot::class);

        try {
            $query = Pilot::onlyTrashed()->with('client');

            /** @var User $user */
            $user = Auth::user();

            // Filtres par client (pour les clients qui ne voient que leurs pilotes)
            if ($user->isClient()) {
                $query->where('client_id', $user->id);
            } elseif ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'deleted_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $pilots = $query->paginate($perPage);

            return PilotResource::collection($pilots);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des pilotes supprimés.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a trashed pilot.
     *
     * @param int $id
     * @return JsonResponse|PilotResource
     */
    public function restore(int $id)
    {
        try {
            $pilot = Pilot::onlyTrashed()->findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Pilote introuvable.',
                'error' => $e->getMessage()
            ], 404);
        }

        $this->authorize('restore', $pilot);

        try {
            $pilot->restore();
            $pilot->load('client');

            return new PilotResource($pilot);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la restauration du pilote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force delete a pilot.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id)
    {
        try {
            $pilot = Pilot::onlyTrashed()->findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Pilote introuvable.',
                'error' => $e->getMessage()
            ], 404);
        }

        $this->authorize('forceDelete', $pilot);

        try {
            $pilot->forceDelete();

            return response()->json([
                'message' => 'Pilote supprimé définitivement.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression définitive du pilote.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pilots statistics.
     *
     * @return JsonResponse
     */
    public function statistics()
    {
        $this->authorize('viewAny', Pilot::class);

        try {
            $query = Pilot::query();

            /** @var User $user */
            $user = Auth::user();

            // Filtrer par client pour les clients
            if ($user->isClient()) {
                $query->where('client_id', $user->id);
            }

            $stats = [
                'total' => $query->count(),
                'minors' => $query->where('is_minor', true)->count(),
                'adults' => $query->where('is_minor', false)->count(),
                'with_email' => $query->whereNotNull('email')->count(),
                'with_phone' => $query->whereNotNull('phone')->count(),
                'deleted' => $user->isClient()
                    ? Pilot::onlyTrashed()->where('client_id', $user->id)->count()
                    : Pilot::onlyTrashed()->count(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKartRequest;
use App\Http\Requests\UpdateKartRequest;
use App\Http\Resources\KartResource;
use App\Models\Kart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class KartController extends Controller
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
        $this->authorize('viewAny', Kart::class);

        try {
            $query = Kart::with(['pilot.client', 'client']);

            /** @var User $user */
            $user = Auth::user();

            // Filtres par client (pour les clients qui ne voient que leurs karts)
            if ($user->isClient()) {
                $query->whereHas('pilot', function ($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            } elseif ($request->filled('client_id')) {
                $query->whereHas('pilot', function ($q) use ($request) {
                    $q->where('client_id', $request->client_id);
                });
            }

            // Filtre par pilote
            if ($request->filled('pilot_id')) {
                $query->where('pilot_id', $request->pilot_id);
            }

            // Filtre par statut
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filtre par marque
            if ($request->filled('brand')) {
                $query->where('brand', 'like', "%{$request->brand}%");
            }

            // Filtre par type de moteur
            if ($request->filled('engine_type')) {
                $query->where('engine_type', $request->engine_type);
            }

            // Filtre par année
            if ($request->filled('year_from')) {
                $query->where('year', '>=', $request->year_from);
            }
            if ($request->filled('year_to')) {
                $query->where('year', '<=', $request->year_to);
            }

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('chassis_number', 'like', "%{$search}%")
                      ->orWhereHas('pilot', function ($pq) use ($search) {
                          $pq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%");
                      });
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'brand');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $karts = $query->paginate($perPage);

            return KartResource::collection($karts);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des karts.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreKartRequest $request
     * @return JsonResponse|KartResource
     */
    public function store(StoreKartRequest $request)
    {
        $this->authorize('create', Kart::class);
        $this->authorize('createForPilot', [Kart::class, $request->pilot_id]);

        try {
            $kart = Kart::create($request->validated());
            $kart->load(['pilot.client', 'client']);

            return new KartResource($kart);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du kart.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Kart $kart
     * @return JsonResponse|KartResource
     */
    public function show(Kart $kart)
    {
        $this->authorize('view', $kart);

        try {
            $kart->load(['pilot.client', 'client']);
            return new KartResource($kart);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération du kart.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateKartRequest $request
     * @param Kart $kart
     * @return JsonResponse|KartResource
     */
    public function update(UpdateKartRequest $request, Kart $kart)
    {
        $this->authorize('update', $kart);

        try {
            // Vérifier l'autorisation pour changer de pilote si nécessaire
            if ($request->filled('pilot_id') && $request->pilot_id != $kart->pilot_id) {
                $this->authorize('createForPilot', [Kart::class, $request->pilot_id]);
            }

            $kart->update($request->validated());
            $kart->load(['pilot.client', 'client']);

            return new KartResource($kart);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du kart.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     *
     * @param Kart $kart
     * @return JsonResponse
     */
    public function destroy(Kart $kart)
    {
        $this->authorize('delete', $kart);

        try {
            $kart->delete();

            return response()->json([
                'message' => 'Kart supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du kart.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of trashed karts.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function trashed(Request $request)
    {
        $this->authorize('viewTrashed', Kart::class);

        try {
            $query = Kart::onlyTrashed()->with(['pilot.client', 'client']);

            /** @var User $user */
            $user = Auth::user();

            // Filtres par client (pour les clients qui ne voient que leurs karts)
            if ($user->isClient()) {
                $query->whereHas('pilot', function ($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            } elseif ($request->filled('client_id')) {
                $query->whereHas('pilot', function ($q) use ($request) {
                    $q->where('client_id', $request->client_id);
                });
            }

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('brand', 'like', "%{$search}%")
                      ->orWhere('model', 'like', "%{$search}%")
                      ->orWhere('chassis_number', 'like', "%{$search}%");
                });
            }

            // Tri
            $sortBy = $request->get('sort_by', 'deleted_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $karts = $query->paginate($perPage);

            return KartResource::collection($karts);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des karts supprimés.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a trashed kart.
     *
     * @param int $id
     * @return JsonResponse|KartResource
     */
    public function restore(int $id)
    {
        try {
            $kart = Kart::onlyTrashed()->findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Kart introuvable.',
                'error' => $e->getMessage()
            ], 404);
        }

        $this->authorize('restore', $kart);

        try {
            $kart->restore();
            $kart->load(['pilot.client', 'client']);

            return new KartResource($kart);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la restauration du kart.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force delete a kart.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id)
    {
        try {
            $kart = Kart::onlyTrashed()->findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Kart introuvable.',
                'error' => $e->getMessage()
            ], 404);
        }

        $this->authorize('forceDelete', $kart);

        try {
            $kart->forceDelete();

            return response()->json([
                'message' => 'Kart supprimé définitivement.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression définitive du kart.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get karts statistics.
     *
     * @return JsonResponse
     */
    public function statistics()
    {
        $this->authorize('viewAny', Kart::class);

        try {
            $query = Kart::query();

            /** @var User $user */
            $user = Auth::user();

            // Filtrer par client pour les clients
            if ($user->isClient()) {
                $query->whereHas('pilot', function ($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            }

            $stats = [
                'total' => $query->count(),
                'active' => $query->where('is_active', true)->count(),
                'inactive' => $query->where('is_active', false)->count(),
                'by_engine_type' => [
                    '2T' => $query->where('engine_type', '2T')->count(),
                    '4T' => $query->where('engine_type', '4T')->count(),
                    'ELECTRIC' => $query->where('engine_type', 'ELECTRIC')->count(),
                ],
                'by_brand' => $query->selectRaw('brand, COUNT(*) as count')
                    ->groupBy('brand')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'vintage' => $query->where('year', '<', 2010)->count(),
                'modern' => $query->where('year', '>=', 2010)->count(),
                'deleted' => $user->isClient()
                    ? Kart::onlyTrashed()->whereHas('pilot', function ($q) use ($user) {
                        $q->where('client_id', $user->id);
                    })->count()
                    : Kart::onlyTrashed()->count(),
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

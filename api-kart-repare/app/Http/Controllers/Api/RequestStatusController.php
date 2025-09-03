<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRequestStatusRequest;
use App\Http\Requests\UpdateRequestStatusRequest;
use App\Http\Resources\RequestStatusResource;
use App\Models\RequestStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class RequestStatusController extends Controller
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
        $this->authorize('viewAny', RequestStatus::class);

        try {
            $query = RequestStatus::query();

            // Filtre par type de statut (final/non-final)
            if ($request->filled('is_final')) {
                $query->where('is_final', $request->boolean('is_final'));
            }

            // Filtre par couleur
            if ($request->filled('hex_color')) {
                $query->where('hex_color', $request->hex_color);
            }

            // Recherche dans les noms
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Tri
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $requestStatuses = $query->paginate($perPage);

            return RequestStatusResource::collection($requestStatuses);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statuts de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRequestStatusRequest $request
     * @return JsonResponse|RequestStatusResource
     */
    public function store(StoreRequestStatusRequest $request)
    {
        $this->authorize('create', RequestStatus::class);

        try {
            $requestStatus = RequestStatus::create($request->validated());

            return new RequestStatusResource($requestStatus);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du statut de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param RequestStatus $requestStatus
     * @return JsonResponse|RequestStatusResource
     */
    public function show(RequestStatus $requestStatus)
    {
        $this->authorize('view', $requestStatus);

        try {
            return new RequestStatusResource($requestStatus);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération du statut de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRequestStatusRequest $request
     * @param RequestStatus $requestStatus
     * @return JsonResponse|RequestStatusResource
     */
    public function update(UpdateRequestStatusRequest $request, RequestStatus $requestStatus)
    {
        $this->authorize('update', $requestStatus);

        try {
            $requestStatus->update($request->validated());

            return new RequestStatusResource($requestStatus);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du statut de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     *
     * @param RequestStatus $requestStatus
     * @return JsonResponse
     */
    public function destroy(RequestStatus $requestStatus)
    {
        $this->authorize('delete', $requestStatus);

        try {
            $requestStatus->delete();

            return response()->json([
                'message' => 'Statut de demande supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du statut de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of trashed request statuses.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function trashed(Request $request)
    {
        $this->authorize('viewTrashed', RequestStatus::class);

        try {
            $query = RequestStatus::onlyTrashed();

            // Recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Tri
            $sortBy = $request->get('sort_by', 'deleted_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $requestStatuses = $query->paginate($perPage);

            return RequestStatusResource::collection($requestStatuses);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statuts supprimés.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore a trashed request status.
     *
     * @param int $id
     * @return JsonResponse|RequestStatusResource
     */
    public function restore(int $id)
    {
        try {
            $requestStatus = RequestStatus::onlyTrashed()->findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Statut de demande introuvable.',
                'error' => $e->getMessage()
            ], 404);
        }

        $this->authorize('restore', $requestStatus);

        try {
            $requestStatus->restore();

            return new RequestStatusResource($requestStatus);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la restauration du statut de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force delete a request status.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id)
    {
        try {
            $requestStatus = RequestStatus::onlyTrashed()->findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Statut de demande introuvable.',
                'error' => $e->getMessage()
            ], 404);
        }

        $this->authorize('forceDelete', $requestStatus);

        try {
            $requestStatus->forceDelete();

            return response()->json([
                'message' => 'Statut de demande supprimé définitivement.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression définitive du statut de demande.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get request statuses statistics.
     *
     * @return JsonResponse
     */
    public function statistics()
    {
        $this->authorize('viewAny', RequestStatus::class);

        try {
            $stats = [
                'total' => RequestStatus::count(),
                'final' => RequestStatus::where('is_final', true)->count(),
                'not_final' => RequestStatus::where('is_final', false)->count(),
                'by_color' => RequestStatus::selectRaw('hex_color, COUNT(*) as count')
                    ->groupBy('hex_color')
                    ->orderBy('count', 'desc')
                    ->get(),
                'most_common_colors' => RequestStatus::selectRaw('hex_color, COUNT(*) as count')
                    ->groupBy('hex_color')
                    ->orderBy('count', 'desc')
                    ->limit(5)
                    ->get(),
                'deleted' => RequestStatus::onlyTrashed()->count(),
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

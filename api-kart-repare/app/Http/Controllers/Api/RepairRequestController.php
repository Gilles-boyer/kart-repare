<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepairRequestRequest;
use App\Http\Requests\UpdateRepairRequestRequest;
use App\Http\Resources\RepairRequestResource;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class RepairRequestController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $this->authorize('viewAny', RepairRequest::class);
        } catch (AuthorizationException $e) {
            Log::warning('Unauthorized access to repair requests', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()?->role,
                'endpoint' => 'repair-requests.index'
            ]);

            return response()->json([
                'message' => 'Vous n\'avez pas l\'autorisation de voir les demandes de réparation.'
            ], 403);
        }

        try {
            $query = RepairRequest::with(['kart', 'status', 'creator', 'assignedUser']);

            /** @var User $user */
            $user = Auth::user();

            // Filtres par utilisateur selon son rôle
            if (!$user->isAdmin() && !$user->isBureauStaff()) {
                $query->where(function (Builder $q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('assigned_to', $user->id);
                });
            }

            // Filtres de recherche
            if ($request->filled('status_id')) {
                $query->where('status_id', $request->status_id);
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->filled('kart_id')) {
                $query->where('kart_id', $request->kart_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filtres par statut de completion
            if ($request->has('is_completed')) {
                if ($request->boolean('is_completed')) {
                    $query->completed();
                } else {
                    $query->active();
                }
            }

            // Filtres par statut de progression
            if ($request->filled('status_filter')) {
                switch ($request->status_filter) {
                    case 'pending':
                        $query->pending();
                        break;
                    case 'active':
                        $query->active();
                        break;
                    case 'completed':
                        $query->completed();
                        break;
                    case 'overdue':
                        $query->overdue();
                        break;
                }
            }

            // Filtres par priorité
            if ($request->filled('priority_filter')) {
                switch ($request->priority_filter) {
                    case 'high':
                        $query->highPriority();
                        break;
                    case 'medium':
                        $query->mediumPriority();
                        break;
                    case 'low':
                        $query->lowPriority();
                        break;
                }
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $allowedSorts = ['created_at', 'updated_at', 'priority', 'estimated_cost', 'estimated_completion', 'title'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $repairRequests = $query->paginate($perPage);

            return RepairRequestResource::collection($repairRequests);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des demandes de réparation', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des demandes de réparation.',
                'data' => []
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRepairRequestRequest $request): JsonResponse
    {
        $this->authorize('create', RepairRequest::class);

        try {
            DB::beginTransaction();

            $repairRequest = RepairRequest::create($request->validated());

            Log::info('Demande de réparation créée', [
                'repair_request_id' => $repairRequest->id,
                'created_by' => $request->user()->id,
                'kart_id' => $repairRequest->kart_id,
                'priority' => $repairRequest->priority,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Demande de réparation créée avec succès.',
                'data' => new RepairRequestResource($repairRequest->load(['kart', 'status', 'creator'])),
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour création demande de réparation', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création demande de réparation', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RepairRequest $repairRequest): JsonResponse
    {
        $this->authorize('view', $repairRequest);

        try {
            $repairRequest->load(['kart', 'status', 'creator', 'assignedUser']);

            return response()->json([
                'data' => new RepairRequestResource($repairRequest),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Demande de réparation non trouvée.',
            ], 404);

        } catch (Exception $e) {
            Log::error('Erreur lors de l\'affichage demande de réparation', [
                'repair_request_id' => $repairRequest->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRepairRequestRequest $request, RepairRequest $repairRequest): JsonResponse
    {
        $this->authorize('update', $repairRequest);

        try {
            DB::beginTransaction();

            $originalData = $repairRequest->toArray();

            $repairRequest->update($request->validated());

            Log::info('Demande de réparation mise à jour', [
                'repair_request_id' => $repairRequest->id,
                'updated_by' => $request->user()->id,
                'changes' => array_diff_assoc($repairRequest->toArray(), $originalData),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Demande de réparation mise à jour avec succès.',
                'data' => new RepairRequestResource($repairRequest->load(['kart', 'status', 'creator', 'assignedUser'])),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour mise à jour demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Demande de réparation non trouvée.',
            ], 404);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RepairRequest $repairRequest): JsonResponse
    {
        $this->authorize('delete', $repairRequest);

        try {
            DB::beginTransaction();

            $title = $repairRequest->title;
            $repairRequest->delete();

            Log::info('Demande de réparation supprimée', [
                'repair_request_id' => $repairRequest->id,
                'title' => $title,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Demande de réparation supprimée avec succès.',
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Demande de réparation non trouvée.',
            ], 404);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Start a repair request.
     */
    public function start(Request $request, RepairRequest $repairRequest): JsonResponse
    {
        $this->authorize('start', $repairRequest);

        try {
            if ($repairRequest->started_at !== null) {
                return response()->json([
                    'message' => 'Cette demande de réparation a déjà été commencée.',
                ], 422);
            }

            DB::beginTransaction();

            $repairRequest->update([
                'started_at' => now(),
            ]);

            Log::info('Demande de réparation commencée', [
                'repair_request_id' => $repairRequest->id,
                'started_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Demande de réparation commencée avec succès.',
                'data' => new RepairRequestResource($repairRequest->fresh(['kart', 'status', 'creator', 'assignedUser'])),
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors du démarrage de la demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors du démarrage de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Complete a repair request.
     */
    public function complete(Request $request, RepairRequest $repairRequest): JsonResponse
    {
        $this->authorize('complete', $repairRequest);

        try {
            if ($repairRequest->completed_at !== null) {
                return response()->json([
                    'message' => 'Cette demande de réparation est déjà terminée.',
                ], 422);
            }

            if ($repairRequest->started_at === null) {
                return response()->json([
                    'message' => 'Cette demande de réparation doit d\'abord être commencée.',
                ], 422);
            }

            $validated = $request->validate([
                'actual_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            ]);

            DB::beginTransaction();

            $updateData = [
                'completed_at' => now(),
            ];

            if (isset($validated['actual_cost'])) {
                $updateData['actual_cost'] = $validated['actual_cost'];
            }

            $repairRequest->update($updateData);

            Log::info('Demande de réparation terminée', [
                'repair_request_id' => $repairRequest->id,
                'completed_by' => $request->user()->id,
                'actual_cost' => $updateData['actual_cost'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Demande de réparation terminée avec succès.',
                'data' => new RepairRequestResource($repairRequest->fresh(['kart', 'status', 'creator', 'assignedUser'])),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour completion demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la completion de la demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la finalisation de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Assign a mechanic to a repair request.
     */
    public function assign(Request $request, RepairRequest $repairRequest): JsonResponse
    {
        $this->authorize('assign', $repairRequest);

        try {
            $validated = $request->validate([
                'mechanic_id' => [
                    'nullable',
                    'integer',
                    'exists:users,id,deleted_at,NULL',
                ],
            ]);

            // Vérifier que l'utilisateur assigné est un mécanicien
            if ($validated['mechanic_id']) {
                $mechanic = User::find($validated['mechanic_id']);
                if (!$mechanic || !$mechanic->canBeMechanic()) {
                    return response()->json([
                        'message' => 'L\'utilisateur sélectionné ne peut pas être assigné à cette tâche.',
                    ], 422);
                }
            }

            DB::beginTransaction();

            $repairRequest->update([
                'assigned_to' => $validated['mechanic_id'],
            ]);

            Log::info('Mécanicien assigné à la demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'assigned_to' => $validated['mechanic_id'],
                'assigned_by' => $request->user()->id,
            ]);

            DB::commit();

            $message = $validated['mechanic_id']
                ? 'Mécanicien assigné avec succès.'
                : 'Assignation supprimée avec succès.';

            return response()->json([
                'message' => $message,
                'data' => new RepairRequestResource($repairRequest->fresh(['kart', 'status', 'creator', 'assignedUser'])),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour assignation demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'assignation de la demande de réparation', [
                'repair_request_id' => $repairRequest->id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'assignation de la demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Get repair request statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewStatistics', RepairRequest::class);
        } catch (AuthorizationException $e) {
            Log::warning('Unauthorized access to repair request statistics', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()?->role,
                'endpoint' => 'repair-requests.statistics'
            ]);

            return response()->json([
                'message' => 'Vous n\'avez pas l\'autorisation de voir les statistiques.'
            ], 403);
        }

        try {
            $stats = [
                'total' => RepairRequest::count(),
                'pending' => RepairRequest::pending()->count(),
                'active' => RepairRequest::active()->count(),
                'completed' => RepairRequest::completed()->count(),
                'overdue' => RepairRequest::overdue()->count(),
                'by_priority' => [
                    'high' => RepairRequest::highPriority()->count(),
                    'medium' => RepairRequest::mediumPriority()->count(),
                    'low' => RepairRequest::lowPriority()->count(),
                ],
                'costs' => [
                    'total_estimated' => (float) RepairRequest::sum('estimated_cost'),
                    'total_actual' => (float) RepairRequest::sum('actual_cost'),
                    'average_cost' => (float) RepairRequest::avg('estimated_cost'),
                ],
                'timeline' => [
                    'this_month' => RepairRequest::whereMonth('created_at', now()->month)->count(),
                    'last_month' => RepairRequest::whereMonth('created_at', now()->subMonth()->month)->count(),
                ],
                'deleted' => RepairRequest::onlyTrashed()->count(),
            ];

            return response()->json($stats);

        } catch (Exception $e) {
            Log::error('Erreur lors de la génération des statistiques', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la génération des statistiques.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }
}

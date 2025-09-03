<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepairRequestProductRequest;
use App\Http\Requests\UpdateRepairRequestProductRequest;
use App\Http\Resources\RepairRequestProductResource;
use App\Models\RepairRequestProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairRequestProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $this->authorize('viewAny', RepairRequestProduct::class);

            $query = RepairRequestProduct::query()
                ->with(['repairRequest', 'product', 'invoicedBy', 'completedBy']);

            // Filter by repair request
            if ($request->has('repair_request_id')) {
                $query->where('repair_request_id', $request->repair_request_id);
            }

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            // Filter by priority
            if ($request->has('priority')) {
                $query->byPriority($request->priority);
            }

            // Filter by workflow state
            if ($request->has('invoiced')) {
                $query->when($request->boolean('invoiced'), function ($q) {
                    return $q->invoiced();
                }, function ($q) {
                    return $q->notInvoiced();
                });
            }

            if ($request->has('completed')) {
                $query->when($request->boolean('completed'), function ($q) {
                    return $q->completed();
                }, function ($q) {
                    return $q->notCompleted();
                });
            }

            if ($request->has('approved')) {
                $query->when($request->boolean('approved'), function ($q) {
                    return $q->approved();
                }, function ($q) {
                    return $q->notApproved();
                });
            }

            // Filter by date range
            if ($request->has('created_from')) {
                $query->createdFrom($request->created_from);
            }

            if ($request->has('created_to')) {
                $query->createdTo($request->created_to);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%");
                })->orWhereHas('repairRequest', function ($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            $allowedSorts = ['created_at', 'updated_at', 'total_price', 'priority', 'invoiced_at', 'completed_at', 'approved_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $repairRequestProducts = $query->paginate($perPage);

            return RepairRequestProductResource::collection($repairRequestProducts);

        } catch (\Exception $e) {
            Log::error('Error fetching repair request products: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des produits de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRepairRequestProductRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('create', RepairRequestProduct::class);

            $repairRequestProduct = RepairRequestProduct::create($request->validated());

            DB::commit();

            Log::info('Repair request product created successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'data' => $request->validated()
            ]);

            return response()->json([
                'message' => 'Produit de demande de réparation créé avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product']))
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error creating repair request product: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        try {
            $this->authorize('view', $repairRequestProduct);

            $repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']);

            return response()->json([
                'data' => new RepairRequestProductResource($repairRequestProduct)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching repair request product: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRepairRequestProductRequest $request, RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('update', $repairRequestProduct);

            $repairRequestProduct->update($request->validated());

            DB::commit();

            Log::info('Repair request product updated successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'data' => $request->validated()
            ]);

            return response()->json([
                'message' => 'Produit de demande de réparation mis à jour avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating repair request product: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Load relations needed for policy
            $repairRequestProduct->load(['repairRequest']);

            // Check if it can be deleted (business rule)
            if (!$repairRequestProduct->canBeDeleted()) {
                return response()->json([
                    'message' => 'Ce produit ne peut pas être supprimé car il a déjà été facturé.'
                ], 403);
            }

            $this->authorize('delete', $repairRequestProduct);

            $repairRequestProduct->delete();

            DB::commit();

            Log::info('Repair request product deleted successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Produit de demande de réparation supprimé avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting repair request product: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Mark the repair request product as invoiced.
     */
    public function invoice(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('invoice', $repairRequestProduct);

            $repairRequestProduct->markAsInvoiced(Auth::user());

            DB::commit();

            Log::info('Repair request product invoiced successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Produit de demande de réparation facturé avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error invoicing repair request product: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la facturation du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Mark the repair request product as completed.
     */
    public function complete(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('complete', $repairRequestProduct);

            $repairRequestProduct->markAsCompleted(Auth::user());

            DB::commit();

            Log::info('Repair request product completed successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Produit de demande de réparation terminé avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error completing repair request product: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la completion du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Mark the repair request product as approved.
     */
    public function approve(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('approve', $repairRequestProduct);

            $repairRequestProduct->markAsApproved();

            DB::commit();

            Log::info('Repair request product approved successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Produit de demande de réparation approuvé avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error approving repair request product: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'approbation du produit de demande de réparation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Revert the invoice status of the repair request product.
     */
    public function revertInvoice(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('revertInvoice', $repairRequestProduct);

            $repairRequestProduct->update([
                'invoiced_by' => null,
                'invoiced_at' => null,
            ]);

            DB::commit();

            Log::info('Repair request product invoice reverted successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Statut de facturation du produit annulé avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error reverting repair request product invoice: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'annulation du statut de facturation.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Revert the completion status of the repair request product.
     */
    public function revertCompletion(RepairRequestProduct $repairRequestProduct): JsonResponse
    {
        DB::beginTransaction();

        try {
            $this->authorize('revertCompletion', $repairRequestProduct);

            $repairRequestProduct->update([
                'completed_by' => null,
                'completed_at' => null,
            ]);

            DB::commit();

            Log::info('Repair request product completion reverted successfully', [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Statut de completion du produit annulé avec succès.',
                'data' => new RepairRequestProductResource($repairRequestProduct->load(['repairRequest', 'product', 'invoicedBy', 'completedBy']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error reverting repair request product completion: ' . $e->getMessage(), [
                'repair_request_product_id' => $repairRequestProduct->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'annulation du statut de completion.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }

    /**
     * Get statistics for repair request products.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', RepairRequestProduct::class);

            $query = RepairRequestProduct::query();

            // Apply date filters if provided
            if ($request->has('from_date')) {
                $query->where('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('created_at', '<=', $request->to_date);
            }

            $statistics = [
                'total_products' => $query->count(),
                'total_value' => $query->sum('total_price'),
                'average_value' => $query->avg('total_price'),
                'invoiced_count' => $query->invoiced()->count(),
                'completed_count' => $query->completed()->count(),
                'approved_count' => $query->approved()->count(),
                'pending_count' => $query->notInvoiced()->count(),
                'by_priority' => [
                    'high' => $query->byPriority('high')->count(),
                    'medium' => $query->byPriority('medium')->count(),
                    'low' => $query->byPriority('low')->count(),
                ],
                'workflow_distribution' => [
                    'created' => $query->notInvoiced()->count(),
                    'invoiced' => $query->invoiced()->notCompleted()->count(),
                    'completed' => $query->completed()->notApproved()->count(),
                    'approved' => $query->approved()->count(),
                ],
            ];

            return response()->json([
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching repair request product statistics: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne s\'est produite.'
            ], 500);
        }
    }
}

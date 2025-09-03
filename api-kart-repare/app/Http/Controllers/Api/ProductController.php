<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        $this->authorize('viewAny', Product::class);

        try {
            $query = Product::query();

            // Filtres de recherche
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%")
                      ->orWhere('sku', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('unity')) {
                $query->where('unity', $request->unity);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->filled('in_stock')) {
                if ($request->boolean('in_stock')) {
                    $query->where('in_stock', '>', 0);
                } else {
                    $query->where('in_stock', '<=', 0);
                }
            }

            if ($request->filled('stock_status')) {
                if ($request->stock_status === 'in_stock') {
                    $query->where('in_stock', '>', 0);
                } elseif ($request->stock_status === 'out_of_stock') {
                    $query->where('in_stock', 0);
                }
            }

            // Tri
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $allowedSorts = ['name', 'price', 'in_stock', 'created_at', 'updated_at'];

            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $products = $query->paginate($perPage);

            return ProductResource::collection($products);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des produits', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des produits.',
                'data' => []
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        try {
            DB::beginTransaction();

            $product = Product::create($request->validated());

            Log::info('Produit créé', [
                'product_id' => $product->id,
                'created_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Produit créé avec succès.',
                'data' => new ProductResource($product),
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour création produit', [
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création produit', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du produit.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        try {
            return response()->json([
                'data' => new ProductResource($product),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Produit non trouvé.',
            ], 404);

        } catch (Exception $e) {
            Log::error('Erreur lors de l\'affichage produit', [
                'product_id' => $product->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération du produit.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        try {
            DB::beginTransaction();

            $originalData = $product->toArray();
            $product->update($request->validated());

            Log::info('Produit mis à jour', [
                'product_id' => $product->id,
                'updated_by' => $request->user()->id,
                'changes' => array_diff_assoc($product->toArray(), $originalData),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Produit mis à jour avec succès.',
                'data' => new ProductResource($product),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour mise à jour produit', [
                'product_id' => $product->id,
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Produit non trouvé.',
            ], 404);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour produit', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'request_data' => $request->validated(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du produit.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        try {
            DB::beginTransaction();

            $name = $product->name;
            $product->delete();

            Log::info('Produit supprimé', [
                'product_id' => $product->id,
                'name' => $name,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Produit supprimé avec succès.',
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Produit non trouvé.',
            ], 404);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression produit', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression du produit.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Get low stock products.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        try {
            $threshold = $request->get('threshold', 10);

            $products = Product::where('in_stock', '>', 0)
                              ->where('in_stock', '<', $threshold)
                              ->orderBy('in_stock', 'asc')
                              ->get();

            return response()->json([
                'message' => "Produits avec stock faible (< {$threshold})",
                'data' => ProductResource::collection($products),
                'count' => $products->count(),
            ]);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des produits en stock faible', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des produits en stock faible.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Get trashed products (soft deleted).
     */
    public function trashed(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        try {
            $products = Product::onlyTrashed()->paginate($request->get('per_page', 15));

            return response()->json([
                'data' => ProductResource::collection($products),
            ]);

        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des produits supprimés', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des produits supprimés.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Restore a soft deleted product.
     */
    public function restore(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $this->authorize('restore', $product);

            DB::beginTransaction();

            $product->restore();

            Log::info('Produit restauré', [
                'product_id' => $product->id,
                'restored_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Produit restauré avec succès.',
                'data' => new ProductResource($product),
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la restauration du produit', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la restauration du produit.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Force delete a product permanently.
     */
    public function forceDelete(Request $request, $id): JsonResponse
    {
        try {
            $product = Product::onlyTrashed()->findOrFail($id);
            $this->authorize('forceDelete', $product);

            DB::beginTransaction();

            $name = $product->name;
            $product->forceDelete();

            Log::info('Produit supprimé définitivement', [
                'product_id' => $id,
                'name' => $name,
                'deleted_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Produit supprimé définitivement avec succès.',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression définitive du produit', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la suppression définitive du produit.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Update product stock.
     */
    public function updateStock(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        try {
            $validated = $request->validate([
                'quantity' => ['required', 'integer', 'min:0'],
                'operation' => ['required', 'string', 'in:set,add,subtract'],
            ]);

            DB::beginTransaction();

            $oldQuantity = $product->in_stock;

            switch ($validated['operation']) {
                case 'set':
                    $product->in_stock = $validated['quantity'];
                    break;
                case 'add':
                    $product->in_stock += $validated['quantity'];
                    break;
                case 'subtract':
                    $product->in_stock = max(0, $product->in_stock - $validated['quantity']);
                    break;
            }

            $product->save();

            Log::info('Stock produit mis à jour', [
                'product_id' => $product->id,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $product->in_stock,
                'operation' => $validated['operation'],
                'updated_by' => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Stock mis à jour avec succès.',
                'data' => new ProductResource($product),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::warning('Validation échouée pour mise à jour stock', [
                'product_id' => $product->id,
                'errors' => $e->errors(),
                'user_id' => $request->user()->id,
            ]);
            throw $e;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour du stock', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du stock.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }

    /**
     * Get product statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', Product::class);

        try {
            $lowStockThreshold = 10;

            $stats = [
                'total' => Product::count(),
                'in_stock' => Product::where('in_stock', '>', 0)->count(),
                'out_of_stock' => Product::where('in_stock', 0)->count(),
                'needs_restock' => Product::where('in_stock', '>', 0)
                                         ->where('in_stock', '<', $lowStockThreshold)
                                         ->count(),
                'by_unity' => Product::selectRaw('unity, COUNT(*) as count')
                                   ->groupBy('unity')
                                   ->pluck('count', 'unity'),
                'prices' => [
                    'min' => Product::min('price') ?? 0,
                    'max' => Product::max('price') ?? 0,
                    'average' => Product::avg('price') ?? 0,
                ],
                'stock_value' => Product::selectRaw('SUM(price * in_stock) as total')->value('total') ?? 0,
                'low_stock_products' => Product::where('in_stock', '>', 0)
                                              ->where('in_stock', '<', $lowStockThreshold)
                                              ->select(['id', 'name', 'in_stock'])
                                              ->get(),
                'deleted' => Product::onlyTrashed()->count(),
            ];

            return response()->json($stats);

        } catch (Exception $e) {
            Log::error('Erreur lors de la génération des statistiques produits', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la génération des statistiques.',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur interne est survenue.',
            ], 500);
        }
    }
}

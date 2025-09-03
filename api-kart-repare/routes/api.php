<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PilotController;
use App\Http\Controllers\Api\KartController;
use App\Http\Controllers\Api\RequestStatusController;
use App\Http\Controllers\Api\RepairRequestController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RepairRequestProductController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route pour récupérer l'utilisateur authentifié (compatible avec l'existant)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes d'authentification publiques
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

    // Routes authentifiées pour l'auth
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    });
});

// Routes protégées par authentification Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Routes pour les utilisateurs
    Route::prefix('users')->group(function () {
        // Route pour récupérer le profil de l'utilisateur connecté
        Route::get('/profile', [UserController::class, 'profile'])->name('users.profile');
        Route::put('/profile', [UserController::class, 'updateProfile'])->name('users.updateProfile');

        // Route pour les statistiques (admin/bureau staff uniquement)
        Route::get('/statistics', [UserController::class, 'statistics'])->name('users.statistics');

        // Routes pour les utilisateurs supprimés (soft delete)
        Route::get('/trashed', [UserController::class, 'trashed'])->name('users.trashed');
        Route::patch('/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/{id}/force-delete', [UserController::class, 'forceDelete'])->name('users.forceDelete');

        // Route pour changer le statut d'un utilisateur
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
    });

    // Resource routes pour CRUD complet des utilisateurs
    Route::apiResource('users', UserController::class)->except(['create', 'edit']);

    // Routes pour les pilotes
    Route::prefix('pilots')->group(function () {
        // Route pour les statistiques des pilotes
        Route::get('/statistics', [PilotController::class, 'statistics'])->name('pilots.statistics');

        // Routes pour les pilotes supprimés (soft delete)
        Route::get('/trashed', [PilotController::class, 'trashed'])->name('pilots.trashed');
        Route::patch('/{id}/restore', [PilotController::class, 'restore'])->name('pilots.restore');
        Route::delete('/{id}/force-delete', [PilotController::class, 'forceDelete'])->name('pilots.forceDelete');
    });

    // Resource routes pour CRUD complet des pilotes
    Route::apiResource('pilots', PilotController::class)->except(['create', 'edit']);

    // Routes pour les karts
    Route::prefix('karts')->group(function () {
        // Route pour les statistiques des karts
        Route::get('/statistics', [KartController::class, 'statistics'])->name('karts.statistics');

        // Routes pour les karts supprimés (soft delete)
        Route::get('/trashed', [KartController::class, 'trashed'])->name('karts.trashed');
        Route::patch('/{id}/restore', [KartController::class, 'restore'])->name('karts.restore');
        Route::delete('/{id}/force-delete', [KartController::class, 'forceDelete'])->name('karts.forceDelete');
    });

    // Resource routes pour CRUD complet des karts
    Route::apiResource('karts', KartController::class)->except(['create', 'edit']);

    // Routes pour les statuts de demande
    Route::prefix('request-statuses')->group(function () {
        // Route pour les statistiques des statuts
        Route::get('/statistics', [RequestStatusController::class, 'statistics'])->name('request-statuses.statistics');

        // Routes pour les statuts supprimés (soft delete)
        Route::get('/trashed', [RequestStatusController::class, 'trashed'])->name('request-statuses.trashed');
        Route::patch('/{id}/restore', [RequestStatusController::class, 'restore'])->name('request-statuses.restore');
        Route::delete('/{id}/force-delete', [RequestStatusController::class, 'forceDelete'])->name('request-statuses.forceDelete');
    });

    // Resource routes pour CRUD complet des statuts de demande
    Route::apiResource('request-statuses', RequestStatusController::class)->except(['create', 'edit']);

    // Routes pour les demandes de réparation
    Route::prefix('repair-requests')->group(function () {
        // Route pour les statistiques des demandes de réparation
        Route::get('/statistics', [RepairRequestController::class, 'statistics'])->name('repair-requests.statistics');

        // Routes pour les demandes supprimées (soft delete)
        Route::get('/trashed', [RepairRequestController::class, 'trashed'])->name('repair-requests.trashed');
        Route::patch('/{id}/restore', [RepairRequestController::class, 'restore'])->name('repair-requests.restore');
        Route::delete('/{id}/force-delete', [RepairRequestController::class, 'forceDelete'])->name('repair-requests.forceDelete');

        // Routes pour les actions spéciales
        Route::patch('/{repair_request}/start', [RepairRequestController::class, 'start'])->name('repair-requests.start');
        Route::patch('/{repair_request}/complete', [RepairRequestController::class, 'complete'])->name('repair-requests.complete');
        Route::patch('/{repair_request}/assign', [RepairRequestController::class, 'assign'])->name('repair-requests.assign');
    });

    // Resource routes pour CRUD complet des demandes de réparation
    Route::apiResource('repair-requests', RepairRequestController::class)->except(['create', 'edit']);

    // Products - routes spécialisées avant apiResource
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('statistics', [ProductController::class, 'statistics'])->name('statistics');
        Route::get('low-stock', [ProductController::class, 'lowStock'])->name('low-stock');
        Route::patch('{product}/stock', [ProductController::class, 'updateStock'])->name('update-stock');

        // Routes de compatibilité pour les tests existants
        Route::post('{product}/stock/add', function(Request $request, Product $product) {
            $request->merge(['operation' => 'add']);
            return app(ProductController::class)->updateStock($request, $product);
        })->name('add-stock');
        Route::post('{product}/stock/reduce', function(Request $request, Product $product) {
            $request->merge(['operation' => 'subtract']);
            return app(ProductController::class)->updateStock($request, $product);
        })->name('reduce-stock');

        Route::get('trashed', [ProductController::class, 'trashed'])->name('trashed');
        Route::patch('{id}/restore', [ProductController::class, 'restore'])->name('restore');
        Route::delete('{id}/force-delete', [ProductController::class, 'forceDelete'])->name('force-delete');
    });

    // Resource routes pour CRUD complet des produits
    Route::apiResource('products', ProductController::class)->except(['create', 'edit']);

    // Routes pour les produits de demande de réparation
    Route::prefix('repair-request-products')->name('repair-request-products.')->group(function () {
        // Routes statistiques et workflow
        Route::get('statistics', [RepairRequestProductController::class, 'statistics'])->name('statistics');

        // Actions workflow spécifiques
        Route::patch('{repair_request_product}/invoice', [RepairRequestProductController::class, 'invoice'])->name('invoice');
        Route::patch('{repair_request_product}/complete', [RepairRequestProductController::class, 'complete'])->name('complete');
        Route::patch('{repair_request_product}/approve', [RepairRequestProductController::class, 'approve'])->name('approve');

        // Actions de révocation workflow
        Route::patch('{repair_request_product}/revert-invoice', [RepairRequestProductController::class, 'revertInvoice'])->name('revert-invoice');
        Route::patch('{repair_request_product}/revert-completion', [RepairRequestProductController::class, 'revertCompletion'])->name('revert-completion');
    });

    // Resource routes pour CRUD complet des produits de demande de réparation
    Route::apiResource('repair-request-products', RepairRequestProductController::class)->except(['create', 'edit']);
});

// Routes de documentation API
Route::get('/docs', function () {
    return response()->json([
        'message' => 'KartRepair API Documentation',
        'version' => '1.0.0',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/login' => 'Connexion utilisateur',
                'POST /api/auth/register' => 'Inscription utilisateur',
                'POST /api/auth/logout' => 'Déconnexion utilisateur',
                'GET /api/auth/me' => 'Profil utilisateur connecté',
            ],
            'users' => [
                'GET /api/users' => 'Liste des utilisateurs',
                'POST /api/users' => 'Créer un utilisateur',
                'GET /api/users/{id}' => 'Détails d\'un utilisateur',
                'PUT /api/users/{id}' => 'Modifier un utilisateur',
                'DELETE /api/users/{id}' => 'Supprimer un utilisateur (soft delete)',
                'GET /api/users/profile' => 'Profil personnel',
                'PUT /api/users/profile' => 'Modifier profil personnel',
                'PATCH /api/users/{id}/toggle-status' => 'Activer/Désactiver utilisateur',
                'GET /api/users/statistics' => 'Statistiques utilisateurs',
                'GET /api/users/trashed' => 'Liste des utilisateurs supprimés',
                'PATCH /api/users/{id}/restore' => 'Restaurer un utilisateur supprimé',
                'DELETE /api/users/{id}/force-delete' => 'Suppression définitive',
            ],
            'pilots' => [
                'GET /api/pilots' => 'Liste des pilotes',
                'POST /api/pilots' => 'Créer un pilote',
                'GET /api/pilots/{id}' => 'Détails d\'un pilote',
                'PUT /api/pilots/{id}' => 'Modifier un pilote',
                'DELETE /api/pilots/{id}' => 'Supprimer un pilote (soft delete)',
                'GET /api/pilots/statistics' => 'Statistiques des pilotes',
                'GET /api/pilots/trashed' => 'Liste des pilotes supprimés',
                'PATCH /api/pilots/{id}/restore' => 'Restaurer un pilote supprimé',
                'DELETE /api/pilots/{id}/force-delete' => 'Suppression définitive',
            ],
            'karts' => [
                'GET /api/karts' => 'Liste des karts',
                'POST /api/karts' => 'Créer un kart',
                'GET /api/karts/{id}' => 'Détails d\'un kart',
                'PUT /api/karts/{id}' => 'Modifier un kart',
                'DELETE /api/karts/{id}' => 'Supprimer un kart (soft delete)',
                'GET /api/karts/statistics' => 'Statistiques des karts',
                'GET /api/karts/trashed' => 'Liste des karts supprimés',
                'PATCH /api/karts/{id}/restore' => 'Restaurer un kart supprimé',
                'DELETE /api/karts/{id}/force-delete' => 'Suppression définitive',
            ],
            'request-statuses' => [
                'GET /api/request-statuses' => 'Liste des statuts de demande',
                'POST /api/request-statuses' => 'Créer un statut de demande',
                'GET /api/request-statuses/{id}' => 'Détails d\'un statut',
                'PUT /api/request-statuses/{id}' => 'Modifier un statut',
                'DELETE /api/request-statuses/{id}' => 'Supprimer un statut (soft delete)',
                'GET /api/request-statuses/statistics' => 'Statistiques des statuts',
                'GET /api/request-statuses/trashed' => 'Liste des statuts supprimés',
                'PATCH /api/request-statuses/{id}/restore' => 'Restaurer un statut supprimé',
                'DELETE /api/request-statuses/{id}/force-delete' => 'Suppression définitive',
            ],
            'repair-requests' => [
                'GET /api/repair-requests' => 'Liste des demandes de réparation',
                'POST /api/repair-requests' => 'Créer une demande de réparation',
                'GET /api/repair-requests/{id}' => 'Détails d\'une demande',
                'PUT /api/repair-requests/{id}' => 'Modifier une demande',
                'DELETE /api/repair-requests/{id}' => 'Supprimer une demande (soft delete)',
                'GET /api/repair-requests/statistics' => 'Statistiques des demandes',
                'GET /api/repair-requests/trashed' => 'Liste des demandes supprimées',
                'PATCH /api/repair-requests/{id}/restore' => 'Restaurer une demande supprimée',
                'DELETE /api/repair-requests/{id}/force-delete' => 'Suppression définitive',
                'PATCH /api/repair-requests/{id}/start' => 'Commencer une réparation',
                'PATCH /api/repair-requests/{id}/complete' => 'Terminer une réparation',
                'PATCH /api/repair-requests/{id}/assign' => 'Assigner un mécanicien',
            ],
            'products' => [
                'GET /api/products' => 'Liste des produits',
                'POST /api/products' => 'Créer un produit',
                'GET /api/products/{id}' => 'Détails d\'un produit',
                'PUT /api/products/{id}' => 'Modifier un produit',
                'DELETE /api/products/{id}' => 'Supprimer un produit (soft delete)',
                'GET /api/products/statistics' => 'Statistiques des produits et stocks',
                'GET /api/products/low-stock' => 'Produits nécessitant un réapprovisionnement',
                'PATCH /api/products/{id}/stock' => 'Mettre à jour le stock (add/remove/set)',
            ],
            'repair-request-products' => [
                'GET /api/repair-request-products' => 'Liste des produits de demande de réparation',
                'POST /api/repair-request-products' => 'Ajouter un produit à une demande de réparation',
                'GET /api/repair-request-products/{id}' => 'Détails d\'un produit de demande',
                'PUT /api/repair-request-products/{id}' => 'Modifier un produit de demande',
                'DELETE /api/repair-request-products/{id}' => 'Supprimer un produit de demande',
                'GET /api/repair-request-products/statistics' => 'Statistiques des produits de demandes',
                'PATCH /api/repair-request-products/{id}/invoice' => 'Facturer un produit',
                'PATCH /api/repair-request-products/{id}/complete' => 'Marquer un produit comme terminé',
                'PATCH /api/repair-request-products/{id}/approve' => 'Approuver un produit',
                'PATCH /api/repair-request-products/{id}/revert-invoice' => 'Annuler la facturation',
                'PATCH /api/repair-request-products/{id}/revert-completion' => 'Annuler la completion',
            ],
        ],
        'authentication' => 'Bearer Token via Laravel Sanctum',
        'base_url' => url('/api'),
    ]);
});

// Route de santé de l'API
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
    ]);
});

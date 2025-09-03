<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can manage stock.
     */
    public function manageStock(User $user, Product $product): bool
    {
        return $user->role === 'admin' || $user->role === 'bureau_staff';
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir la liste des produits
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir un produit
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Seuls les admins et le personnel du bureau peuvent créer des produits
        return in_array($user->role, ['admin', 'bureau_staff']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // Seuls les admins et le personnel du bureau peuvent modifier des produits
        return in_array($user->role, ['admin', 'bureau_staff']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // Seuls les admins peuvent supprimer des produits
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        // Seuls les admins peuvent restaurer des produits supprimés
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Seuls les admins peuvent supprimer définitivement des produits
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view stock statistics.
     */
    public function viewStatistics(User $user): bool
    {
        // Admins et personnel du bureau peuvent voir les statistiques
        return in_array($user->role, ['admin', 'bureau_staff']);
    }
}

<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les utilisateurs
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les utilisateurs.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin peut voir tous les utilisateurs
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Bureau staff peut voir clients et mécaniciens
        if ($user->isBureauStaff() && in_array($model->role, ['client', 'mechanic'])) {
            return Response::allow();
        }

        // Un utilisateur peut voir son propre profil
        if ($user->id === $model->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir ce profil.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent créer des utilisateurs
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de créer des utilisateurs.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin peut modifier tous les utilisateurs
        if ($user->isAdmin()) {
            return Response::allow();
        }

        // Bureau staff peut modifier clients et mécaniciens
        if ($user->isBureauStaff() && in_array($model->role, ['client', 'mechanic'])) {
            return Response::allow();
        }

        // Un utilisateur peut modifier son propre profil (sauf le rôle)
        if ($user->id === $model->id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de modifier ce profil.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seul admin peut supprimer des utilisateurs
        if ($user->isAdmin()) {
            // Ne peut pas se supprimer lui-même
            if ($user->id === $model->id) {
                return Response::deny('Vous ne pouvez pas supprimer votre propre compte.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer des utilisateurs.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seul admin peut restaurer des utilisateurs
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de restaurer des utilisateurs.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seul admin peut définitivement supprimer des utilisateurs
        if ($user->isAdmin()) {
            // Ne peut pas se supprimer lui-même
            if ($user->id === $model->id) {
                return Response::deny('Vous ne pouvez pas supprimer définitivement votre propre compte.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer définitivement des utilisateurs.');
    }

    /**
     * Determine whether the user can change roles.
     */
    public function changeRole(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seul admin peut changer les rôles
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de changer les rôles.');
    }

    /**
     * Determine whether the user can activate/deactivate users.
     */
    public function toggleStatus(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin peut activer/désactiver tous les utilisateurs
        if ($user->isAdmin()) {
            // Ne peut pas se désactiver lui-même
            if ($user->id === $model->id && $model->is_active) {
                return Response::deny('Vous ne pouvez pas désactiver votre propre compte.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de changer le statut des utilisateurs.');
    }

    /**
     * Determine whether the user can view trashed users.
     */
    public function viewTrashed(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent voir les utilisateurs supprimés
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les utilisateurs supprimés.');
    }

    /**
     * Determine whether the user can restore a soft deleted user.
     */
    public function restoreUser(?User $user = null, User $model): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seul admin peut restaurer des utilisateurs
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de restaurer des utilisateurs.');
    }
}

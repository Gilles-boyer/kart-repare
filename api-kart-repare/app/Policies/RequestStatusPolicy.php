<?php

namespace App\Policies;

use App\Models\RequestStatus;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RequestStatusPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Tout utilisateur authentifié peut voir les statuts de demande
        return Response::allow();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user = null, RequestStatus $requestStatus): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Tout utilisateur authentifié peut voir un statut de demande
        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent créer des statuts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de créer des statuts de demande.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user = null, RequestStatus $requestStatus): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent modifier des statuts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de modifier ce statut de demande.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user = null, RequestStatus $requestStatus): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent supprimer des statuts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer ce statut de demande.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user = null, RequestStatus $requestStatus): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent restaurer des statuts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de restaurer ce statut de demande.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user = null, RequestStatus $requestStatus): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin peuvent supprimer définitivement
        if ($user->isAdmin()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer définitivement ce statut de demande.');
    }

    /**
     * Determine whether the user can view trashed request statuses.
     */
    public function viewTrashed(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent voir les statuts supprimés
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les statuts supprimés.');
    }
}

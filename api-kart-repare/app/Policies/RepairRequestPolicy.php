<?php

namespace App\Policies;

use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepairRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir les demandes de réparation
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RepairRequest $repairRequest): bool
    {
        // Les utilisateurs peuvent voir:
        // - Leurs propres demandes
        // - Les demandes qui leur sont assignées
        // - Tous les admins/bureau_staff peuvent voir toutes les demandes
        return $user->role === 'admin' ||
               $user->role === 'bureau_staff' ||
               $repairRequest->created_by === $user->id ||
               $repairRequest->assigned_to === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent créer des demandes de réparation
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RepairRequest $repairRequest): Response
    {
        // Seuls les admins, bureau_staff et le créateur peuvent modifier
        if ($user->role === 'admin' || $user->role === 'bureau_staff') {
            return Response::allow();
        }

        if ($repairRequest->created_by === $user->id) {
            // Le créateur peut modifier seulement si pas encore commencée
            if ($repairRequest->started_at === null) {
                return Response::allow();
            }
            return Response::deny('Modification impossible: la demande a ete commencee.');
        }

        if ($repairRequest->assigned_to === $user->id) {
            // Le mécanicien assigné peut modifier certains champs
            return Response::allow('Vous pouvez mettre a jour le statut et les couts.');
        }

        return Response::deny('Autorisation insuffisante pour modifier cette demande.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RepairRequest $repairRequest): Response
    {
        // Seuls les admins et bureau_staff peuvent supprimer
        if ($user->role !== 'admin' && $user->role !== 'bureau_staff') {
            return Response::deny('Seuls les administrateurs peuvent supprimer les demandes.');
        }

        // Ne peut pas supprimer si déjà commencée
        if ($repairRequest->started_at !== null) {
            return Response::deny('Impossible de supprimer une demande deja commencee.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RepairRequest $repairRequest): Response
    {
        if ($user->role !== 'admin' && $user->role !== 'bureau_staff') {
            return Response::deny('Seuls les administrateurs peuvent restaurer les demandes.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RepairRequest $repairRequest): Response
    {
        if ($user->role !== 'admin') {
            return Response::deny('Seuls les administrateurs peuvent supprimer definitivement.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can assign mechanics to repair requests.
     */
    public function assign(User $user, RepairRequest $repairRequest): Response
    {
        if ($user->role !== 'admin' && $user->role !== 'bureau_staff') {
            return Response::deny('Seuls les administrateurs peuvent assigner des mecaniciens.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can start a repair request.
     */
    public function start(User $user, RepairRequest $repairRequest): Response
    {
        // Seuls les admins, bureau_staff ou le mécanicien assigné peuvent commencer
        if ($user->role === 'admin' || $user->role === 'bureau_staff') {
            return Response::allow();
        }

        if ($repairRequest->assigned_to === $user->id) {
            if ($repairRequest->started_at !== null) {
                return Response::deny('Cette demande a deja ete commencee.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n etes pas autorise a commencer cette reparation.');
    }

    /**
     * Determine whether the user can complete a repair request.
     */
    public function complete(User $user, RepairRequest $repairRequest): Response
    {
        // Seuls les admins, bureau_staff ou le mécanicien assigné peuvent terminer
        if ($user->role === 'admin' || $user->role === 'bureau_staff') {
            return Response::allow();
        }

        if ($repairRequest->assigned_to === $user->id) {
            if ($repairRequest->started_at === null) {
                return Response::deny('Cette demande doit d abord etre commencee.');
            }
            if ($repairRequest->completed_at !== null) {
                return Response::deny('Cette demande est deja terminee.');
            }
            return Response::allow();
        }

        return Response::deny('Vous n etes pas autorise a terminer cette reparation.');
    }

    /**
     * Determine whether the user can view statistics.
     */
    public function viewStatistics(User $user): Response
    {
        if ($user->role === 'admin' || $user->role === 'bureau_staff') {
            return Response::allow();
        }

        return Response::deny('Seuls les administrateurs peuvent consulter les statistiques.');
    }
}

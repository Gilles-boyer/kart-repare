<?php

namespace App\Policies;

use App\Models\Pilot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PilotPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les pilotes
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Les clients peuvent voir leurs propres pilotes
        if ($user->isClient()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les pilotes.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user = null, Pilot $pilot): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les pilotes
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut voir ses propres pilotes
        if ($user->isClient() && $user->id === $pilot->client_id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir ce pilote.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin, bureau staff et clients peuvent créer des pilotes
        if ($user->isAdmin() || $user->isBureauStaff() || $user->isClient()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de créer des pilotes.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user = null, Pilot $pilot): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent modifier tous les pilotes
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut modifier ses propres pilotes
        if ($user->isClient() && $user->id === $pilot->client_id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de modifier ce pilote.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user = null, Pilot $pilot): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent supprimer tous les pilotes
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut supprimer ses propres pilotes
        if ($user->isClient() && $user->id === $pilot->client_id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer ce pilote.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user = null, Pilot $pilot): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent restaurer tous les pilotes
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut restaurer ses propres pilotes
        if ($user->isClient() && $user->id === $pilot->client_id) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de restaurer ce pilote.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user = null, Pilot $pilot): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent supprimer définitivement
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer définitivement ce pilote.');
    }

    /**
     * Determine whether the user can view trashed pilots.
     */
    public function viewTrashed(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les pilotes supprimés
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut voir ses propres pilotes supprimés
        if ($user->isClient()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les pilotes supprimés.');
    }

    /**
     * Determine whether the user can create a pilot for a specific client.
     */
    public function createForClient(?User $user = null, int $clientId): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent créer des pilotes pour n\'importe quel client
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut créer des pilotes pour lui-même uniquement
        if ($user->isClient() && $user->id === $clientId) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de créer un pilote pour ce client.');
    }
}

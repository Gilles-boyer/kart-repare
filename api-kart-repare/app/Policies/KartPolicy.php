<?php

namespace App\Policies;

use App\Models\Kart;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KartPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les karts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Les clients peuvent voir leurs karts (via leurs pilotes)
        if ($user->isClient()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les karts.');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user = null, Kart $kart): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les karts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut voir les karts de ses pilotes
        if ($user->isClient()) {
            $kart->loadMissing(['pilot.client']);
            if ($kart->pilot && $user->id === $kart->pilot->client_id) {
                return Response::allow();
            }
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir ce kart.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin, bureau staff et clients peuvent créer des karts
        if ($user->isAdmin() || $user->isBureauStaff() || $user->isClient()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de créer des karts.');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(?User $user = null, Kart $kart): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent modifier tous les karts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut modifier les karts de ses pilotes
        if ($user->isClient()) {
            $kart->loadMissing(['pilot.client']);
            if ($kart->pilot && $user->id === $kart->pilot->client_id) {
                return Response::allow();
            }
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de modifier ce kart.');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(?User $user = null, Kart $kart): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent supprimer tous les karts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut supprimer les karts de ses pilotes
        if ($user->isClient()) {
            $kart->loadMissing(['pilot.client']);
            if ($kart->pilot && $user->id === $kart->pilot->client_id) {
                return Response::allow();
            }
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer ce kart.');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(?User $user = null, Kart $kart): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent restaurer tous les karts
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut restaurer les karts de ses pilotes
        if ($user->isClient()) {
            $kart->loadMissing(['pilot.client']);
            if ($kart->pilot && $user->id === $kart->pilot->client_id) {
                return Response::allow();
            }
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de restaurer ce kart.');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(?User $user = null, Kart $kart): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Seuls admin et bureau staff peuvent supprimer définitivement
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de supprimer définitivement ce kart.');
    }

    /**
     * Determine whether the user can view trashed karts.
     */
    public function viewTrashed(?User $user = null): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent voir tous les karts supprimés
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut voir ses karts supprimés (via ses pilotes)
        if ($user->isClient()) {
            return Response::allow();
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de voir les karts supprimés.');
    }

    /**
     * Determine whether the user can create a kart for a specific pilot.
     */
    public function createForPilot(?User $user = null, int $pilotId): Response
    {
        if (!$user) {
            return Response::deny('Vous devez être connecté.');
        }

        // Admin et bureau staff peuvent créer des karts pour n'importe quel pilote
        if ($user->isAdmin() || $user->isBureauStaff()) {
            return Response::allow();
        }

        // Un client peut créer des karts pour ses propres pilotes uniquement
        if ($user->isClient()) {
            $pilot = \App\Models\Pilot::find($pilotId);
            if ($pilot && $user->id === $pilot->client_id) {
                return Response::allow();
            }
        }

        return Response::deny('Vous n\'avez pas l\'autorisation de créer un kart pour ce pilote.');
    }
}

<?php

namespace App\Policies;

use App\Models\RepairRequestProduct;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepairRequestProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'bureau_staff', 'mechanic', 'client']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Admin and bureau_staff can view all
        if ($user->hasAnyRole(['admin', 'bureau_staff'])) {
            return true;
        }

        // Mechanics can view repair request products they're assigned to
        if ($user->hasRole('mechanic')) {
            return $repairRequestProduct->repairRequest->assigned_to === $user->id;
        }

        // Clients can view repair request products for their own karts/requests
        if ($user->hasRole('client')) {
            return $repairRequestProduct->repairRequest->client_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'bureau_staff', 'mechanic']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Admin and bureau_staff can update all
        if ($user->hasAnyRole(['admin', 'bureau_staff'])) {
            return true;
        }

        // Mechanics can update repair request products they're assigned to
        if ($user->hasRole('mechanic')) {
            return $repairRequestProduct->repairRequest->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin and bureau_staff can delete
        if (!$user->hasAnyRole(['admin', 'bureau_staff'])) {
            return false;
        }

        // Cannot delete if already invoiced
        if ($repairRequestProduct->is_invoiced) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        return $user->hasAnyRole(['admin', 'bureau_staff']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can invoice the repair request product.
     */
    public function invoice(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin, bureau_staff, and assigned mechanic can invoice
        if (!$user->hasAnyRole(['admin', 'bureau_staff', 'mechanic'])) {
            return false;
        }

        // Mechanics can only invoice products they're assigned to
        if ($user->hasRole('mechanic') && !$user->hasAnyRole(['admin', 'bureau_staff'])) {
            if ($repairRequestProduct->repairRequest->assigned_to !== $user->id) {
                return false;
            }
        }

        // Check if the product can be invoiced
        if (!$repairRequestProduct->canBeInvoiced()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can mark the repair request product as completed.
     */
    public function complete(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin, bureau_staff, and assigned mechanic can mark as completed
        if (!$user->hasAnyRole(['admin', 'bureau_staff', 'mechanic'])) {
            return false;
        }

        // Mechanics can only complete products they're assigned to
        if ($user->hasRole('mechanic') && !$user->hasAnyRole(['admin', 'bureau_staff'])) {
            if ($repairRequestProduct->repairRequest->assigned_to !== $user->id) {
                return false;
            }
        }

        // Check if the product can be completed
        if (!$repairRequestProduct->canBeCompleted()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can approve the repair request product.
     */
    public function approve(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin and bureau_staff can approve
        if (!$user->hasAnyRole(['admin', 'bureau_staff'])) {
            return false;
        }

        // Check if the product can be approved
        if (!$repairRequestProduct->canBeApproved()) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can revert the invoice status.
     */
    public function revertInvoice(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin and bureau_staff can revert invoice
        if (!$user->hasAnyRole(['admin', 'bureau_staff'])) {
            return false;
        }

        // Cannot revert if already completed or approved
        if ($repairRequestProduct->is_completed || $repairRequestProduct->is_approved) {
            return false;
        }

        return $repairRequestProduct->is_invoiced;
    }

    /**
     * Determine whether the user can revert the completion status.
     */
    public function revertCompletion(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin and bureau_staff can revert completion
        if (!$user->hasAnyRole(['admin', 'bureau_staff'])) {
            return false;
        }

        // Cannot revert if already approved
        if ($repairRequestProduct->is_approved) {
            return false;
        }

        return $repairRequestProduct->is_completed;
    }

    /**
     * Determine whether the user can modify workflow-related fields.
     */
    public function updateWorkflow(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        return $user->hasAnyRole(['admin', 'bureau_staff']);
    }

    /**
     * Determine whether the user can view financial information (prices, totals).
     */
    public function viewFinancials(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Admin and bureau_staff can always view financials
        if ($user->hasAnyRole(['admin', 'bureau_staff'])) {
            return true;
        }

        // Mechanics can view financials for assigned repair requests
        if ($user->hasRole('mechanic')) {
            return $repairRequestProduct->repairRequest->assigned_to === $user->id;
        }

        // Clients cannot view detailed financial information
        return false;
    }

    /**
     * Determine whether the user can modify prices.
     */
    public function updatePricing(User $user, RepairRequestProduct $repairRequestProduct): bool
    {
        // Only admin and bureau_staff can modify pricing
        if (!$user->hasAnyRole(['admin', 'bureau_staff'])) {
            return false;
        }

        // Cannot modify pricing if already invoiced
        if ($repairRequestProduct->is_invoiced) {
            return false;
        }

        return true;
    }
}

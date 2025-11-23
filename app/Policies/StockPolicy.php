<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    /**
     * Determine if the user can view any stocks.
     * READ-ONLY: Semua user bisa melihat
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the stock.
     * READ-ONLY: Semua user bisa melihat
     */
    public function view(User $user, Stock $stock): bool
    {
        return true;
    }

    /**
     * Determine if the user can create stocks.
     * LOCKED: Hanya system (via Observer) yang bisa create
     */
    public function create(User $user): bool
    {
        return false; // ALWAYS FALSE - System-managed only
    }

    /**
     * Determine if the user can update the stock.
     * LOCKED: Hanya system (via Observer) yang bisa update
     */
    public function update(User $user, Stock $stock): bool
    {
        return false; // ALWAYS FALSE - System-managed only
    }

    /**
     * Determine if the user can delete the stock.
     * LOCKED: Hanya system (via Observer) yang bisa delete
     */
    public function delete(User $user, Stock $stock): bool
    {
        return false; // ALWAYS FALSE - System-managed only
    }

    /**
     * Determine if the user can restore the stock.
     * LOCKED: Tidak ada restore manual
     */
    public function restore(User $user, Stock $stock): bool
    {
        return false;
    }

    /**
     * Determine if the user can permanently delete the stock.
     * LOCKED: Tidak ada force delete manual
     */
    public function forceDelete(User $user, Stock $stock): bool
    {
        return false;
    }
}

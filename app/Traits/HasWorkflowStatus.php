<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasWorkflowStatus
{
    /**
     * Transition ke status baru dengan validation
     */
    public function transitionTo(string $newStatus, array $data = []): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        // Merge additional data
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }

        // Auto-set updated_by
        if (Auth::check()) {
            $this->updated_by = Auth::id();
        }

        return $this->save();
    }

    /**
     * Check apakah bisa transition ke status tertentu
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowedTransitions = $this->getAllowedTransitions();

        return isset($allowedTransitions[$this->status])
            && in_array($newStatus, $allowedTransitions[$this->status]);
    }

    /**
     * Get allowed transitions untuk current status
     */
    abstract protected function getAllowedTransitions(): array;

    /**
     * Get next possible statuses
     */
    public function getNextStatuses(): array
    {
        $transitions = $this->getAllowedTransitions();
        return $transitions[$this->status] ?? [];
    }

    /**
     * Check apakah status adalah final
     */
    public function isFinalStatus(): bool
    {
        return in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    /**
     * Check apakah bisa di-cancel
     */
    public function canBeCancelled(): bool
    {
        return !$this->isFinalStatus() && $this->status !== 'cancelled';
    }
}

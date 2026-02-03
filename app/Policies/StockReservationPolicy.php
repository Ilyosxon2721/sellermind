<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse\StockReservation;

class StockReservationPolicy
{
    protected function role(User $user): ?string
    {
        return $user->pivot?->role ?? $user->role ?? null;
    }

    protected function canEdit(User $user): bool
    {
        $role = $this->role($user);

        return in_array($role, ['owner', 'manager', 'warehouse']);
    }

    public function viewAny(User $user): bool
    {
        return (bool) $this->role($user);
    }

    public function view(User $user, StockReservation $reservation): bool
    {
        return $reservation->company_id === ($user->company_id ?? $user->pivot?->company_id) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function update(User $user, StockReservation $reservation): bool
    {
        return $reservation->status === StockReservation::STATUS_ACTIVE && $this->canEdit($user);
    }
}

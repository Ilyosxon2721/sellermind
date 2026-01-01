<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse\InventoryDocument;

class InventoryDocumentPolicy
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

    public function view(User $user, InventoryDocument $document): bool
    {
        return $document->company_id === ($user->company_id ?? $user->pivot?->company_id) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canEdit($user);
    }

    public function update(User $user, InventoryDocument $document): bool
    {
        return $document->status === InventoryDocument::STATUS_DRAFT && $this->canEdit($user);
    }

    public function post(User $user, InventoryDocument $document): bool
    {
        return $document->status === InventoryDocument::STATUS_DRAFT && $this->canEdit($user);
    }

    public function reverse(User $user, InventoryDocument $document): bool
    {
        return $this->canEdit($user);
    }
}

<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Warehouse\InventoryDocument;
use Illuminate\Foundation\Auth\User as Authenticatable;

class InventoryDocumentPolicy
{
    protected function role(Authenticatable $user): ?string
    {
        if ($user instanceof Admin) {
            return 'owner';
        }

        return $user->pivot?->role ?? $user->role ?? null;
    }

    protected function canEdit(Authenticatable $user): bool
    {
        $role = $this->role($user);

        return in_array($role, ['owner', 'manager', 'warehouse']);
    }

    public function viewAny(Authenticatable $user): bool
    {
        return (bool) $this->role($user);
    }

    public function view(Authenticatable $user, InventoryDocument $document): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return $document->company_id === ($user->company_id ?? $user->pivot?->company_id) && $this->viewAny($user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->canEdit($user);
    }

    public function update(Authenticatable $user, InventoryDocument $document): bool
    {
        return $document->status === InventoryDocument::STATUS_DRAFT && $this->canEdit($user);
    }

    public function post(Authenticatable $user, InventoryDocument $document): bool
    {
        return $document->status === InventoryDocument::STATUS_DRAFT && $this->canEdit($user);
    }

    public function reverse(Authenticatable $user, InventoryDocument $document): bool
    {
        return $this->canEdit($user);
    }
}

<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    /**
     * Scope to filter records by user's branch
     */
    public function scopeForUserBranch(Builder $query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isOwner() || $user->isSuperAdmin()) {
            return $query;
        }

        if ($user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope to filter by specific branch
     */
    public function scopeForBranch(Builder $query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}

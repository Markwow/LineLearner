<?php

namespace App\Policies;

use App\Models\Script;
use App\Models\User;

class ScriptPolicy
{
    /**
     * Admins can reach any script; everyone else only their own. Scripts with no
     * owner (created before accounts existed) are admin-only until assigned.
     */
    public function view(User $user, Script $script): bool
    {
        return $user->isAdmin() || ($script->user_id !== null && $script->user_id === $user->id);
    }

    public function update(User $user, Script $script): bool
    {
        return $this->view($user, $script);
    }

    public function delete(User $user, Script $script): bool
    {
        return $this->view($user, $script);
    }
}

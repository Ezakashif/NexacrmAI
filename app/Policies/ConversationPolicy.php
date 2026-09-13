<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use App\Policies\Concerns\ChecksSameCompany;

class ConversationPolicy
{
    use ChecksSameCompany;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view.inbox');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->sameCompany($user, $conversation)
            && $user->hasPermission('view.inbox');
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        return $this->sameCompany($user, $conversation)
            && $user->hasPermission('reply.inbox');
    }

    public function assign(User $user, Conversation $conversation): bool
    {
        return $this->sameCompany($user, $conversation)
            && $user->hasPermission('assign.inbox');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $this->reply($user, $conversation) || $this->assign($user, $conversation);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntranceRole
{
    public function handle(Request $request, Closure $next, string $minimumRole = 'user'): Response
    {
        $minimum = UserRole::from($minimumRole);
        $userRole = $request->user()?->role ?? UserRole::User;

        if ($this->priority($userRole) < $this->priority($minimum)) {
            abort(403, 'Insufficient role for this operation.');
        }

        return $next($request);
    }

    private function priority(UserRole $role): int
    {
        return match ($role) {
            UserRole::Superadmin => 4,
            UserRole::Admin => 3,
            UserRole::Moderator => 2,
            UserRole::User => 1,
        };
    }
}

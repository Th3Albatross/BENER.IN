<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to one or more roles, e.g. `role:petugas` or `role:siswa,petugas`.
 * Roles are compared case-insensitively against the user's stored role.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $currentRole = mb_strtolower(trim((string) $user->role));
        $allowedRoles = array_map(
            fn (string $role): string => mb_strtolower(trim($role)),
            $roles
        );

        abort_unless(in_array($currentRole, $allowedRoles, true), 403);

        return $next($request);
    }
}

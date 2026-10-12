<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $u = $request->user();
        if (!$u || !$u->is_active || !in_array($u->role, $roles, true)) {
            abort(403, 'Your role does not have access to this page.');
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AccountSecurity
{
    public function handle(Request $r, Closure $next)
    {
        if ($u = $r->user()) {
            abort_unless($u->is_active, 403, 'Your account is inactive.');
            if ($u->must_change_password && ! $r->routeIs('password.*', 'logout')) {
                return redirect()->route('password.edit');
            }
        }

        return $next($r);
    }
}

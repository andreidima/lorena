<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$user = Auth::user()) {
            return redirect()->route('login');
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Nu ai permisiunea pentru această acțiune.');
        }

        return $next($request);
    }
}

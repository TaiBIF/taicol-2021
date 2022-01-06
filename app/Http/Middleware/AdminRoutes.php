<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Http\Request;

class AdminRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()->role_id !== User::ROLE_ADMIN) {
            return response([
                'messages' => 'Permission deny.',
            ])->setStatusCode(401);
        }

        return $next($request);
    }
}

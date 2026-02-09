<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoAuthenticateUser
{
    /**
     * Handle an incoming request.
     *
     * Auto-authenticates the first user for mobile MVP.
     * In production, replace with proper mobile authentication.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            $user = User::first();

            // Create a default user if none exists
            if (! $user) {
                $user = User::factory()->create([
                    'name' => 'Mobile User',
                    'email' => 'user@scrolling-through.app',
                ]);
            }

            Auth::login($user);
        }

        return $next($request);
    }
}

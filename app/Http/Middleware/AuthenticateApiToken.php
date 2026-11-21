<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated. Missing bearer token.'], 401);
        }

        $userId = Cache::get('api_token:' . hash('sha256', $token));
        $user = $userId ? User::find($userId) : null;

        if (!$user || $user->status !== 'Active') {
            return response()->json(['message' => 'Invalid or expired session token.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}

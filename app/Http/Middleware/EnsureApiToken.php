<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (blank($token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $user = User::query()->where('api_token', $token)->first();

        if (! $user) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        // Check if token has expired
        if ($user->token_expires_at && $user->token_expires_at->isPast()) {
            return response()->json(['message' => 'Token has expired. Please re-authenticate.'], 401);
        }

        $request->attributes->set('acars_user', $user);

        return $next($request);
    }
}

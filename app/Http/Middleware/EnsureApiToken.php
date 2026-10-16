<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
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

        $apiToken = ApiToken::where('token', $token)->with('user')->first();

        if (! $apiToken) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        // Check if token has expired
        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return response()->json(['message' => 'Token has expired. Please re-authenticate.'], 401);
        }

        // Update last used timestamp
        $apiToken->update(['last_used_at' => now()]);

        $request->attributes->set('acars_user', $apiToken->user);

        return $next($request);
    }
}

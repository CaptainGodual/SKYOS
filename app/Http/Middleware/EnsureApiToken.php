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

        $apiToken = ApiToken::where('token', ApiToken::hashToken($token))->with('user')->first();

        if (! $apiToken) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        // Check if token has expired
        if ($apiToken->isExpired()) {
            return response()->json(['message' => 'Token has expired. Please re-authenticate.'], 401);
        }

        // Approval is re-checked on every request so that revoking a pilot's
        // access also revokes any ACARS token that is still within its lifetime.
        if (! $apiToken->user?->isApproved()) {
            return response()->json(['message' => 'Your account is not approved for ACARS access.'], 403);
        }

        // Update last used timestamp
        $apiToken->update(['last_used_at' => now()]);

        $request->attributes->set('acars_user', $apiToken->user);

        return $next($request);
    }
}

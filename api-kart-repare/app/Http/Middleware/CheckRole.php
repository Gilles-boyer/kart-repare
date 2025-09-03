<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Non authentifié'
            ], 401);
        }

        $user = $request->user();

        // Vérifier si l'utilisateur est actif
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Votre compte est désactivé'
            ], 403);
        }

        // Vérifier si l'utilisateur a l'un des rôles requis
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Accès non autorisé pour votre rôle'
            ], 403);
        }

        return $next($request);
    }
}

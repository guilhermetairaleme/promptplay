<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
       $user = Auth::user();

        // Se não estiver logado, bloqueia
        if (!$user) {
            abort(403);
        }

        // Se for admin e estiver em /prompt-generator sem nome → redireciona com nome
        if ($user->is_admin && $request->is('prompt-generator') && !$request->route('name')) {
            return redirect()->to('/prompt-generator/' . urlencode($user->name));
        }

        // Se NÃO for admin e estiver tentando acessar com nome → redireciona para padrão
        if (!$user->is_admin && $request->route('name')) {
            return redirect('/prompt-generator');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan pengguna sudah login
        if (Auth::check()) {
            $user = Auth::user();

            // Pastikan pengguna bukan halaman user.inactive itu sendiri,
            // untuk menghindari redirect loop
            if ($request->route()->getName() !== 'user.inactive' &&  $request->route()->getName() !== 'logout') {
                // Cek status pengguna. Asumsikan 'status' adalah boolean atau 1/0
                if (!$user->status) {
                    return redirect()->route('user.inactive');
                }
            }
        }

        return $next($request);
    }
}

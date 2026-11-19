<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * STUB PBI-13. Jika PBI-1/autentikasi tim sudah punya middleware role sendiri,
 * HAPUS file ini dan pakai middleware milik tim di routes/pbi13.php.
 *
 * Asumsi: tabel users punya kolom `role` ('kader' | 'orang_tua').
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}

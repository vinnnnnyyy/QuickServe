<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTableToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $tableId = session('table_id');

        if (!$tableId) {
            return redirect()->route('scan.qr');
        }

        $table = \App\Models\Table::find($tableId);

        if (!$table) {
            session()->forget(['table_id', 'table_number']);
            return redirect()->route('scan.qr');
        }

        return $next($request);
    }
}

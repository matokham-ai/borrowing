<?php

namespace App\Http\Middleware;

use App\Support\Auditor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditAdminActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $routeName = $request->route()?->getName() ?? 'unnamed';

            Auditor::log('admin.' . $routeName, null, [
                'description' => 'Admin action: ' . $routeName,
                'context' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                ],
            ]);
        }

        return $response;
    }
}

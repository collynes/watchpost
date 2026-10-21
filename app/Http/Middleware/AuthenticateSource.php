<?php

namespace App\Http\Middleware;

use App\Models\Source;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSource
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->isJson(), 415, 'Use application/json.');
        abort_if(strlen($request->getContent()) > 1048576, 413, 'Maximum request size is 1 MiB.');
        $token = $request->bearerToken();
        abort_unless($token && strlen($token) <= 256, 401, 'Source credential required.');
        $source = Source::where('token_hash', hash('sha256', $token))->where('enabled', true)->where('ingestion_method', 'webhook')->first();
        abort_unless($source, 401, 'Invalid source credential.');
        $request->attributes->set('source', $source);

        return $next($request);
    }
}

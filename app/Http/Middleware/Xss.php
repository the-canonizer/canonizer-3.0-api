<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Xss
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();
        if (empty($input)) {
            $raw = $request->getContent();
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $input = $decoded;
                }
            }
        }
        array_walk_recursive($input, function (&$val) {
            $val = strip_tags($val);
        });
        $request->merge($input);
        $request->json()->replace($input);
        return $next($request);
    }
}

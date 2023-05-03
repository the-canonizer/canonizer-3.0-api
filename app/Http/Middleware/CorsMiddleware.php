<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $allowedOrigins = explode(',', env('ACCESS_CONTROL_ALLOW_ORIGIN'));

        if (in_array($request->header('origin'), $allowedOrigins)) {
            $origin = $request->header('origin');
        } else {
            $origin = 'http://localhost:4000'; //'https://canonizer3.canonizer.com';
        }

        $headers = [
            'Access-Control-Allow-Origin'      => $origin,
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
        ];

        if ($request->isMethod('OPTIONS')) {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $mysqlConfig = config('database.connections.mysql');
        $testDBConfig = config('database.connections.mysql_testing');

        $isFromTestCases = $request->get('from_test_case', null);
        if ($isFromTestCases == '1') {
            config(['database.connections.mysql' => $testDBConfig]);
        }

        $response = $next($request);
        config(['database.connections.mysql' => $mysqlConfig]);
        foreach ($headers as $key => $value) {
            if (strpos($request->url(), 'api/v3')) {
                $response->header($key, $value);
            }
        }

        return $response;
    }
}
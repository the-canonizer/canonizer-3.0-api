<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ErrorResource;

class CheckStatus
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

        $response = $next($request);
        $user = User::where('email', '=', $request->username)->first();

        //If the status is not approved redirect to login 
        if ($user->status != 1) {
            $status = 402;
            $message = trans('message.error.account_not_verified');
            $res = (object) [
                "status_code" => $status,
                "message"     => $message,
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode($status);
        }
        if ($user->is_active != 1) {
            $status = 402;
            $message = trans('message.error.in_active_message');
            $res = (object) [
                "status_code" => $status,
                "message"     => $message,
                "error"       => null,
                "data"        => null
            ];
            return (new ErrorResource($res))->response()->setStatusCode($status);
            
        }
        return $response;
    }
}

<?php

namespace App\Http\Middleware;

use App\Http\Resources\ErrorResource;
use Closure;
use Exception;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;
use Laravel\Passport\Http\Middleware\CheckCredentials;
use Laravel\Passport\Exceptions\MissingScopeException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Throwable;

class CheckClientCredentialsMiddleware extends CheckCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        $psr = (new PsrHttpFactory(
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory
        ))->createRequest($request);

        try {
            $authorizationHeader = $request->header('Authorization');

            // dd(auth()->check());

            if (strpos($authorizationHeader, 'Bearer ') === 0) {
                $tokenId = substr($authorizationHeader, 7);
                // dd($tokenId);

                $tokenRepository = new TokenRepository();
                $token = $tokenRepository->find($tokenId);

                // dd($token);
                // Use the token ID to validate the token
            } else {
                // Invalid authorization header
                return response()->json(['message' => 'Invalid authorization header'], 401);
            }

            $psr = $this->server->validateAuthenticatedRequest($psr);
        } catch (Throwable $e) {
            // dd("i am custom hh", $e->getCode(), $e->getLine(), $e->getMessage(), get_class($e));
            // throw new Exception("Server Error", 500);
            // if ($e instanceof AuthenticationException) {
            //     $res = (object)[
            //         "status_code" => 401,
            //         "message"     => "",
            //         "error"       => "Invalid token or expired token",
            //         "data"        => NULL
            //     ];
            //     return (new ErrorResource($res))->response()->setStatusCode(401);
            // }
            throw new AuthenticationException;
        }

        $this->validate($psr, $scopes);

        return $next($request);
    }

        /**
     * Validate token credentials.
     *
     * @param  \Laravel\Passport\Token  $token
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function validateCredentials($token)
    {

        if (! $token) {
            dd("token not found");
            throw new AuthenticationException;
        }
    }

    /**
     * Validate token credentials.
     *
     * @param  \Laravel\Passport\Token  $token
     * @param  array  $scopes
     * @return void
     *
     * @throws \Laravel\Passport\Exceptions\MissingScopeException
     */
    protected function validateScopes($token, $scopes)
    {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}

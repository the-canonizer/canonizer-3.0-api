<?php
namespace App\Http\Middleware;
use App\Http\Resources\ErrorResource;
use Closure;
use Exception;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;
use Laravel\Passport\Http\Middleware\CheckCredentials;
use Laravel\Passport\Exceptions\MissingScopeException;
use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\TokenRepository;
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

        if (env('PASSPORT_BYPASS_CLIENT_CREDENTIALS', false)) {
            return $next($request);
        }
        try {
            $authorizationHeader = $request->header('Authorization');

            if (strpos($authorizationHeader, 'Bearer ') === 0) {
                $tokenId = substr($authorizationHeader, 7);
                
                $tokenRepository = new TokenRepository();
                $token = $tokenRepository->find($tokenId);
            } else {
                // Invalid authorization header
                return response()->json(['message' => 'Invalid authorization header'], 403);
            }
            $psr = $this->server->validateAuthenticatedRequest($psr);
        } catch (Throwable $e) {
            $res = (object)[
                "status_code" => 403,
                "message"     => "",
                "error"       => "Invalid token or expired token",
                "data"        => NULL
            ];
            return (new ErrorResource($res))->response()->setStatusCode(403);
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
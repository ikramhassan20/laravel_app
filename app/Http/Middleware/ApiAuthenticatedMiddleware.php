<?php

namespace App\Http\Middleware;

use App\Components\AppStatusCodes;
use App\Components\ParseResponse;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Laravel\Passport\Bridge\AccessTokenRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class ApiAuthenticatedMiddleware
{
    use ParseResponse;

    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        try {
            $this->authenticate($guards);

            $valid = $this->validateAuthorizationToken($request);

            if ($valid === false) {
                throw new \Exception('You must be authenticated to use this resource');
            }

            return $next($request);
        } catch (\Exception $exception) {
            $this->addResponse(
                AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'error',
                [$exception->getMessage()],
                'error'
            );
        }
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate(array $guards)
    {
        if (empty($guards)) {
            return $this->auth->authenticate();
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        throw new AuthenticationException('Unauthenticated.', $guards);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function validateAuthorizationToken(Request $request)
    {
        try {
            $psr = (new DiactorosFactory())->createRequest($request);
            $tokens = new TokenRepository();
            $server = new ResourceServer(
                app()->make(AccessTokenRepository::class),
                (
                    new CryptKey(
                        'file://'.Passport::keyPath('oauth-public.key'),
                        null,
                        false
                    )
                )
            );

            $psr = $server->validateAuthenticatedRequest($psr);

            $token = $tokens->find(
                $psr->getAttribute('oauth_access_token_id')
            );

            if ((bool) $token->revoked === true) {
                throw new \Exception("Invalid user credentials");
            }

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }
}

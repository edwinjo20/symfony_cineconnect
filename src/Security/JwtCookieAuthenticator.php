<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JwtCookieAuthenticator extends AbstractAuthenticator
{
    private JWTTokenManagerInterface $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->cookies->has('BEARER');  // ✅ Check if BEARER cookie exists
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->cookies->get('BEARER');  // ✅ Retrieve JWT from cookie

        if (!$token) {
            throw new AuthenticationException('No token found in cookies.');
        }

        return new Passport(
            new UserBadge($token, function ($userIdentifier) {
                return $this->jwtManager->parse($userIdentifier);
            }),
            new CustomCredentials(
                function ($credentials, $user) {
                    return $user !== null;
                },
                $token
            ),
            [new RememberMeBadge()]
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Authentication failed.'], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token, string $firewallName): ?Response
    {
        return null; // ✅ Continue processing the request after authentication
    }
}

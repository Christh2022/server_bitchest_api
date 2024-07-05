<?php

// src/Security/CsrfTokenAuthenticator.php
namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

class CsrfTokenAuthenticator extends AbstractAuthenticator
{
    private $csrfTokenManager;
    private $userProvider;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager, UserProviderInterface $userProvider)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST') && $request->getPathInfo() === '/login';
    }

    public function authenticate(Request $request): Passport
    {
        $data = json_decode($request->getContent(), true);

        // if (!isset($data['email'], $data['password'], $data['csrf_token'])) {
        //     throw new AuthenticationException('Invalid request data');
        // }

        return new Passport(
            new UserBadge($data['email'], function ($userIdentifier) {
                return $this->userProvider->loadUserByIdentifier($userIdentifier);
            }),
            new PasswordCredentials($data['password']),
            [
                new CsrfTokenBadge('authenticate', $data['csrf_token'])
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new JsonResponse(['message' => 'Authentication successful'], 200);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        
        return new JsonResponse(['message' => $exception->getMessage()], 403);
    }

    public function start(Request $request, AuthenticationException $authException = null): ?Response
    {
        return new JsonResponse(['message' => 'Authentication required'], 401);
    }
}

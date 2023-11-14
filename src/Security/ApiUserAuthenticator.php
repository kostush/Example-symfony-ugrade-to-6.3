<?php

namespace App\Security;

use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Repository\ApiUserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiUserAuthenticator extends AbstractGuardAuthenticator
{
    private static $authRoutes = [
        'disribusion_import',
        'distribusion_rides',
        'omio_import',
        'wemovo_import',
        'mycicero_import',
        'urbi_import'
    ];

    /**
     * @var ApiUserRepository
     */
    private $userRepository;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(ApiUserRepository $userRepository, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function supports(Request $request)
    {
        return in_array($request->attributes->get('_route'), self::$authRoutes);
    }

    public function getCredentials(Request $request)
    {
        return [
            'username' => $request->headers->get('php-auth-user'),
            'password' => $request->headers->get('php-auth-pw')
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $this->userRepository->findOneBy(['username' => $credentials['username']]);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new ApiException((new ApiProblem(ApiProblem::TYPE_USER_CREDENTIALS_INVALID)));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // todo
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        // todo
    }

    public function supportsRememberMe()
    {
        // todo
    }
}

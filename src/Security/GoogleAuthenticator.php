<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 
 */
class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry         $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface        $router
    ) {}

    public function supports(Request $request): ?bool
{
    return $request->attributes->get('_route') === 'connect_google_check';
}

    public function authenticate(Request $request): Passport
    {
        $client      = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $email      = $googleUser->getEmail();
                $googleId   = $googleUser->getId();

                // Chercher par Google ID → email → créer
                $user = $this->em->getRepository(User::class)->findOneBy(['googleId' => $googleId])
                     ?? $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setNom($googleUser->getLastName() ?? 'Google');
                    $user->setPrenom($googleUser->getFirstName() ?? 'User');
                    $user->setTelephone('00000000');
                    $user->setPassword(bin2hex(random_bytes(16)));
                    $user->setRole(['ROLE_USER']);
                }

                $user->setGoogleId($googleId);
                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $route = in_array('ROLE_ADMIN', $token->getUser()->getRoles())
            ? 'app_admin_dashboard'
            : 'app_home';

        return new RedirectResponse($this->router->generate($route));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}

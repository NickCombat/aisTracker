<?php

namespace App\Security;

use App\Entity\SecUser; // <--- Passen Sie dies an Ihre User-Entity an!
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use App\Entity\LoginLog;

class AzureAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        // Dieser Authenticator springt NUR an, wenn Microsoft zurückleitet
        return $request->attributes->get('_route') === 'connect_azure_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('azure_main');

        $client->setAsStateless();
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {

                /** @var AzureResourceOwner $azureUser */
                $azureUser = $client->fetchUserFromToken($accessToken);

                // Wir holen die E-Mail (User Principal Name) von Azure
                $email = $azureUser->getUpn();
                if (!$email) {
                    $email = $azureUser->claim('email');
                }

                // PRÜFUNG: Gibt es diesen User schon in unserer Datenbank?
                // Passen Sie 'SecUser' an!
                $user = $this->em->getRepository(SecUser::class)->findOneBy(['email' => $email]);

                if (!$user) {
                    // OPTION A: Harte Tür. Wer nicht manuell angelegt wurde, kommt nicht rein.
                    throw new AuthenticationException('Kein Account für diese E-Mail gefunden.');

                    // OPTION B: Auto-Registration (Falls gewünscht, hier einkommentieren)
                    /*
                    $user = new SecUser();
                    $user->setEmail($email);
                    $user->setPassword(sha1(uniqid())); // Zufallspasswort, da Login via SSO
                    $user->setRoles(['ROLE_USER']);
                    // Ggf. Vorname/Nachname aus $azureUser->getFirstName() holen
                    $this->em->persist($user);
                    $this->em->flush();
                    */
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Ziel nach Login
        return new RedirectResponse($this->router->generate('projekte_uebersicht_list'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());
        return new Response("Login fehlgeschlagen: $message", 403);
    }
}
<?php

namespace App\Controller\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route as RouteAnnotation;

class AzureLoginController extends AbstractController
{
    #[RouteAnnotation('/connect/azure', name: 'connect_azure_start')]
    public function connect(ClientRegistry $clientRegistry)
    {
        $client = $clientRegistry->getClient('azure_main');

        // KORREKTUR: Direkt auf dem Client aufrufen!
        $client->setAsStateless();

        return $client->redirect(['openid', 'profile', 'email', 'User.Read']);
    }

    #[RouteAnnotation('/connect/azure/check', name: 'connect_azure_check')]
    public function connectCheck()
    {
        // Wird vom Authenticator abgefangen, muss aber existieren!
        return $this->redirectToRoute('projekte_uebersicht_list');
    }

    #[RouteAnnotation('/keep-alive', name: 'app_keep_alive', methods: ['POST'])]
    public function keepAlive(): \Symfony\Component\HttpFoundation\JsonResponse
    {
        // Macht nichts, außer zu antworten.
        // Durch den Aufruf wird aber das Session-Cookie beim Server verlängert.
        return $this->json(['status' => 'alive']);
    }
}
<?php

declare( strict_types=1 );

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\NetShipNavStatusRepository;
use Symfony\Component\Mailer\MailerInterface;
use App\Service\SettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class _extensController extends AbstractController
{

    protected EntityManagerInterface $em;

    protected string $rawLogPath;

    /**
     * @var array|string[]
     */
    protected array $logPaths;

    public function __construct(
        protected readonly EntityManagerInterface   $entityManager,
        protected readonly KernelInterface          $kernel,
        private readonly LoggerInterface            $logger,
        private readonly RequestStack               $requestStack
    )
    {
        $projectDir       = $this->kernel->getProjectDir();
        $this->rawLogPath = $projectDir . '/var/log/vesselfinder_raw/';
        $this->logPaths   = [ $projectDir . '/var/log/aisstream_raw/',
                              $this->rawLogPath
        ];
        $this->em = $this->entityManager;
    }

    /**
     * Loggt eine Nachricht ODER fügt sie als Flash-Message hinzu,
     * je nach Ausführungskontext (Web oder CLI).
     */
    protected function logOrFlash( string $level, string $message ): void
    {
        try
        {
            $session = $this->requestStack->getSession();
            $session->getFlashBag()
                    ->add( $level, $message );
            $this->logger->{$level}( $message );
        }
        catch ( SessionNotFoundException $e )
        {
            $this->logger->{$level}( $message );
        }
    }
}

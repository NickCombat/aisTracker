<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Entity\NetShipdata;
use App\Repository\NetShipdataRepository;
use App\Service\VesselFinderService;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Filesystem\Filesystem;

final class RecoverAisApiDatenController extends _extensController
{

    #[Route('/recover/ais/api/daten', name: 'app_recover_ais_api_daten')]
    public function recoverAisApiDaten(): Response
    {
        $dateiListe = [];
        foreach ($this->logPaths as $path)
        {
            $dateiListe = array_merge($dateiListe, $this->listDirectoryContents($path));
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.net_einstellungen',
                'route' => 'net_einstellungen'
            ],
            [
                'label' => 'breadcrumb.ais.recover', // Current page (no link)
            ]
        ];

        return $this->render('recover_ais_api_daten/index.html.twig',
            [ 'headline'               => 'Einstellungen AIS Recover',
              'breadcrumbs'            => $breadcrumbs,
              'dateiListe'             => $dateiListe,

        ]);
    }

    #[Route("/recover/ais/api/preview/{filename}", name: "app_recover_ais_preview", methods: ["GET"])]
    public function getAisJsonPreview(string $filename, Filesystem $filesystem): Response
    {
        $trueFilePath = false;
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            return new Response('Ungültiger Dateiname.', Response::HTTP_BAD_REQUEST);
        }

        // Zielverzeichnis (muss mit der obigen Funktion übereinstimmen)
        foreach ($this->logPaths as $path)
        {
            $filePath = $path . '/' . $filename;

            if ( $filesystem->exists( $filePath ) )
            {
                $trueFilePath = $filePath;
            }
        }

        if(! $trueFilePath )
        {
            return new Response( 'Datei nicht gefunden: ' . $filename, Response::HTTP_NOT_FOUND );
        }

        // Lese den Inhalt der Datei
        $content = file_get_contents( $trueFilePath );

        // 'text/plain' zurück, an das JavaScript
        return new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }

    /**
     * @param Request               $request
     * @param NetShipdataRepository $shipRepository
     * @param VesselFinderService   $vesselFinderService
     * @return Response
     */
    #[Route( '/recover-work-ais-api-data', name: 'app_work_recover_ais_api_daten', methods: ['POST' ] )]
    public function workRecoverAisApiDaten(
        Request               $request,
        VesselFinderService   $vesselFinderService,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response
    {
        $token = new CsrfToken('recover-token', $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token))
        {
            $this->logOrFlash('error', 'Ungültige Anfrage (CSRF-Token fehlt oder ist falsch)['. $token .'].');
            return $this->redirectToRoute('app_recover_ais_api_daten');
        }

        $allPostData = $request->request->all();
        $fileArray = $allPostData['fileArray'] ?? null;
        foreach ( $fileArray as $filename )
        {
            $this->logOrFlash( 'info', 'foreach: ' . $filename );
            $ship = $this->fetchShipFromFilename($filename);
            if ( ! $ship )
            {
                $this->logOrFlash( 'error', 'Schiff nicht gefunden.' );

                return $this->redirectToRoute( 'app_recover_ais_api_daten' );
            }
            $this->logOrFlash( 'info', 'Schiff geladen: ' . $ship->getName() );

            $fileArrayString = file_get_contents( $this->rawLogPath . $filename );
            try
            {
                $file = json_decode( $fileArrayString, true );
                if ( json_last_error() !== JSON_ERROR_NONE )
                {
                    $this->addFlash( 'error', 'Fehler beim Parsen der Daten (ungültiges JSON).' );
                    continue;
                }
                if(isset($file[0]['PORTCALL']))
                {
                    $this->logOrFlash( 'info', 'Verarbeitung PORTCALL' );
                    $vesselFinderService->savePortCalls( $ship, $file );
                }
                elseif(isset($file[0]['AIS']))
                {
                    $this->logOrFlash( 'info', 'Verarbeitung AIS' );
                    $vesselFinderService->updateVessel( $ship, $file[0]['AIS'] );
                }

                $this->logOrFlash( 'notice', 'Daten wurden Verarbeitet.' );
            }
            catch ( \Exception $e )
            {
                $this->logOrFlash( 'error', 'Fehler bei der Verarbeitung. [' . $e->getMessage() . ']' );
            }
        }

        return $this->redirect( $this->generateUrl( 'app_recover_ais_api_daten' ) );
    }

    /**
     * @param Request               $request
     * @param NetShipdataRepository $shipRepository
     * @param VesselFinderService   $vesselFinderService
     * @return Response
     */
    #[Route( '/recover-work-ais-api-file', name: 'app_work_recover_ais_api_file', methods: ['GET'] )]
    public function workRecoverAisApiFile(
        Request               $request,
        VesselFinderService   $vesselFinderService
    ): Response
    {
        $filename = $request->query->get( 'restore' );
        $ship = $this->fetchShipFromFilename( $filename );
        if ( ! $ship )
        {
            $this->logOrFlash( 'error', 'Schiff nicht gefunden.' );
            return $this->redirectToRoute( 'app_recover_ais_api_daten' );
        }
        $this->logOrFlash( 'info', 'Schiff geladen: ' . $ship->getName() );

        $fileArrayString = file_get_contents( $this->rawLogPath . $filename );
        try
        {
            $file = json_decode( $fileArrayString, true );
            if ( json_last_error() !== JSON_ERROR_NONE )
            {
                $this->addFlash( 'error', 'Fehler beim Parsen der Daten (ungültiges JSON).' );
                return $this->redirectToRoute( 'app_recover_ais_api_daten' );
            }
            if ( isset( $file[0]['PORTCALL'] ) )
            {
                $this->logOrFlash( 'info', 'Verarbeitung PORTCALL' );
                $vesselFinderService->savePortCalls( $ship, $file );
            }
            elseif ( isset( $file[0]['AIS'] ) )
            {
                $this->logOrFlash( 'info', 'Verarbeitung AIS' );
                $vesselFinderService->updateVessel( $ship, $file[0]['AIS'] );
            }

            $this->logOrFlash( 'notice', 'Daten wurden Verarbeitet.' );
        }
        catch ( \Exception $e )
        {
            $this->logOrFlash( 'error', 'Fehler bei der Verarbeitung. [' . $e->getMessage() . ']' );
        }

        return $this->redirect( $this->generateUrl( 'app_recover_ais_api_daten' ) );
    }

    /**
     * @param string $dateiName
     * @return NetShipdata|null
     */
    private function fetchShipFromFilename( string $dateiName ):?NetShipdata
    {
        $nameStrip    = explode('.', $dateiName);
        $dateiKennung = explode('_', $nameStrip[0]);
        if(isset($dateiKennung[3]))
        {
            if(substr($dateiKennung[3],0,3)==='imo')
            {
                return $this->em->getRepository(NetShipdata::class)->findOneBy(['imo' => substr($dateiKennung[3],3)]);
            }
            elseif(substr($dateiKennung[3],0,4)==='mmsi')
            {
                return $this->em->getRepository(NetShipdata::class)->findOneBy(['MMSI' => substr($dateiKennung[3],4)]);
            }
            elseif(substr($dateiKennung[2],0,3)==='imo')
            {
                return $this->em->getRepository(NetShipdata::class)->findOneBy(['imo' => substr($dateiKennung[2],3)]);
            }
            elseif(substr($dateiKennung[2],0,4)==='mmsi')
            {
                return $this->em->getRepository(NetShipdata::class)->findOneBy(['MMSI' => substr($dateiKennung[2],4)]);
            }
        }

        return null;
    }

    /**
     * Listet den Inhalt eines Verzeichnisses auf, begrenzt auf maximal 500 Einträge.
     *
     * @param string $dir Das zu lesende Verzeichnis.
     * @return array
     */
    private function listDirectoryContents( string $dir ): array
    {
        // Maximal erlaubte Einträge
        $maxEntries = 250;

        // Prüfen, ob Verzeichnis existiert und lesbar ist
        if ( ! is_dir( $dir ) )
        {
            $this->logOrFlash( 'error', "Fehler: '$dir' ist kein gültiges Verzeichnis.");

            return[];
        }
        if ( ! is_readable( $dir ) )
        {
            $this->logOrFlash( 'error', "Fehler: Verzeichnis '$dir' ist nicht lesbar.");

            return[];
        }

        $dateiArray = [];
        $entryCount = 0; // Neuer Zähler für die Begrenzung

        if ( $handle = opendir($dir) )
        {
            $i=0;
            // einlesen des Verzeichnisses
            while (($file = readdir($handle)) !== false)
            {
                if ($entryCount >= $maxEntries) {
                    break;
                }

                $i++;
                $datei = [];

                // Punkt-Einträge überspringen
                if('.' === $file || '.' === substr($file,1))
                    continue;

                $dateiName = htmlspecialchars( $file );
                $dateiKey  = substr( $dateiName, 0, 19 );
                $fullPath  = $dir . $file;

                $datei['size'] = filesize($fullPath);
                $datei['date'] = filemtime($fullPath);
                $dateiKey      = $datei['date'] + $i;
                $shipData      = $this->fetchShipFromFilename($dateiName);

                if($datei['size'] <= 2)
                {
                    continue;
                }

                $datei['name'] = $dateiName;
                $datei['ship'] = $shipData;

                if(null === $datei['ship'])
                {
                    $datei['ship']['name'] = '- na -';
                }

                $dateiArray[$dateiKey] = $datei;
                $entryCount++; // Zähler erhöhen, nachdem ein gültiger Eintrag hinzugefügt wurde
            }
            closedir($handle);
        }

        krsort($dateiArray);

        return $dateiArray;
    }

}

<?php

namespace App\Controller;

use App\Entity\NetPort;
use App\Entity\NetShipdata;
use App\Entity\NetShipdataPort;
use App\Form\NetShipdataModType;
use App\Form\NetShipdataType;
use App\Repository\NetKomponentenStrukturRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NetShipdataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\NetProjektStatus;
use App\Entity\NetBayStructure;
use App\Service\VesselFinderService;
use App\Entity\NetShipdataPortLog;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Repository\NetKomponentenRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\NetBayStructureRepository;
use App\Service\GalerieService;
use App\Entity\NetShipPositionHistory;

#[Route( '/projekt/uebersicht' )]
class ProjekteUebersichtController extends GalerieService
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $em;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $em)
    {
        $this->translator = $translator;
        $this->em         = $em;
    }

    #[Route('/', name: 'app_projekte_uebersicht')]
    public function index(): Response
    {
        return $this->redirectToRoute('projekte_uebersicht_list');
    }

    #[Route('/liste', name: 'projekte_uebersicht_list')]
    public function liste(?NetShipdataRepository $shipdataRepository, EntityManagerInterface $em): Response
    {
        try
        {
            $shipdataObject = $shipdataRepository->findShipdataByStatusNullOrOneWithStats();
        }
        catch (\Exception $e)
        {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('app_home');
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home',
                'route' => 'app_home'
            ],
            [
                'label' => 'breadcrumb.projects_overview',
                'route' => 'projekte_uebersicht_list'
            ]
        ];

        //$hafenListe = $em->getRepository(NetShipdataPort::class)->findNextPortPerShip();
        $hafenListe = array();

        return $this->render('projekte_uebersicht/index.html.twig', [
            'headline'   => 'Projekte Übersicht',
            'breadcrumbs'=> $breadcrumbs,
            'hafenListe' => $hafenListe,
            'shipdatas'  => $shipdataObject,
        ]);
    }

    #[Route('/projekte/sortieren', name: 'projekt_sortieren', methods: ['POST'])]
    public function sortieren(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        file_put_contents(__DIR__ . '/../../var/log/debug.txt', print_r($data, true));

        $data = json_decode($request->getContent(), true);

        foreach ($data as $item) {
            $projekt = $entityManager->getRepository(NetShipdata::class)->find($item['id']);
            if ($projekt) {
                $projekt->setOrderno($item['order']);
                $entityManager->persist($projekt);
            }
        }

        $entityManager->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/archiv', name: 'projekt_archiv_list')]
    #[IsGranted('ROLE_USER')]
    public function archiv(NetShipdataRepository $shipdataRepository, EntityManagerInterface $em): Response
    {
        $statusObj = $em->getRepository(NetProjektStatus::class)->findBy(['bezeichnung' => 'archiv']);
        $shipdataObject = $shipdataRepository->findBy(['status' => $statusObj]);
        //findShipdataByStatusNullOrOneWithStats
        $breadcrumb  = '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekte_uebersicht_list') . '">Projekte Übersicht</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Archiv</a></li>';

        return $this->render('projekte_uebersicht/index.html.twig', [
            'headline'   => 'Projekt Archiv',
            'breadcrumb' => $breadcrumb,
            'shipdatas'  => $shipdataObject,
        ]);
    }

    #[Route('/api/liste', name: 'api_projekte_list')]
    public function apiList(NetShipdataRepository $shipdataReposetory)
    {
        $netShipDatas    = $shipdataReposetory->findShipdataByStatusNullOrOne();

        $projektArray = [];
        $projektArray[] = '';
        foreach ( $netShipDatas as $netShipData )
        {
            $id    = $netShipData->getId();
            $name  = $netShipData->getName();
            $projektArray[$id] = $name;
        }

        return new JsonResponse($projektArray);
    }

    #[Route('/details/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_deails')]
    public function details(int $id, NetShipdataRepository $shipdataReposetory, EntityManagerInterface $em): Response
    {
        //$shipdataObject = $shipdataReposetory->findByIdExtend($id);
        $shipdataObject = $shipdataReposetory->find($id);

        $headline = '' . $shipdataObject->getName();
        if ($shipdataObject->getImo() != '0')
        {
            $headline .= ' - ' . $shipdataObject->getImo();
        }

        $currentDate = (new \DateTime())->format('Y-m-d');

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.projects_overview',
                'route' => 'projekte_uebersicht_list'
            ],
            [
                'label' => 'breadcrumb.project_detail', // Key for "Projekt %name%"
                'params' => ['%name%' => $shipdataObject->getName()], // Parameters for translation
                'route' => 'projekt_deails',
                'routeParams' => ['id' => $shipdataObject->getId()] // Parameters for the route
            ],
            [
                'label' => 'breadcrumb.project_components' // Current page (no link)
            ]
        ];

        return $this->render('projekte_uebersicht/detailsEdit.html.twig', [
            'headline'    => $headline,
            'breadcrumbs' => $breadcrumbs,
            'projekt_id'  => $shipdataObject->getId(),
            'shipdata'    => $shipdataObject,
            'currentDate' => $currentDate,
            'anlagenArray'=> $shipdataObject->getNetProjektAnlagens(),
        ]);
    }  
    
    #[Route('/bearbeiten/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_bearbeiten')]
    #[IsGranted('ROLE_USER')]
    public function bearbeiten(int $id, NetShipdataRepository $shipdataReposetory, Request $request, EntityManagerInterface $em): Response
    {
        $shipdataObject = $shipdataReposetory->find($id);

        $form = $this->createForm(NetShipdataModType::class, $shipdataObject);
        $form->handleRequest($request);

        if($form->isSubmitted())
        {
            try
            {
                $anlage = '';
                if ( $request->files->get( 'net_shipdata' ) )
                {
                    $anlage = $request->files->get( 'net_shipdata' )['Schiffsbild'];
                }
                if ( $request->files->get( 'net_shipdata_mod' ) )
                {
                    $anlage = $request->files->get( 'net_shipdata_mod' )['Schiffsbild'];
                }
                if ( $anlage )
                {
                    $destination  = $this->getParameter( 'anlagenShipOrdner' );
                    $dateiname = md5( uniqid() ) . '.' . $anlage->guessClientExtension();
                    // Pfad zur temporären Datei (direkt nach dem Upload)
                    $tempPath  = $anlage->getRealPath();
                    $finalPath = $destination . '/' . $dateiname;

                    // Bild Kompression aufrufen
                    $this->compressAndScaleImage($tempPath, $finalPath);
                    $shipdataObject->setPic( $dateiname );
                }
                $this->updateStatus($shipdataObject, $shipdataObject->getStatus(), $em);
                $em->persist( $shipdataObject );
                $em->flush();

                $this->addFlash( 'success', 'Das Projekt wurde erfolgreich geändert.' );
            }
            catch ( \Exception $e )
            {
                $this->addFlash( 'success', 'Fehler beim ändern des Projekts. [' . $e->getMessage() . ']' );
            }

            return $this->redirect( $this->generateUrl( 'projekt_bearbeiten', [ 'id' => $id ] ) );
        }

                $breadcrumb  = '<li class="breadcrumb-item"><a href="/">';
        $breadcrumb .= $this->translator->trans('Home');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekte_uebersicht_list') . '">';
        $breadcrumb .= $this->translator->trans('Projekte Übersicht');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekt_deails', [ 'id' => $shipdataObject->getId() ]) . '">';
        $breadcrumb .= $this->translator->trans('Projekt') . ' ' . $shipdataObject->getName();
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">';
        $breadcrumb .= $this->translator->trans('Projekt bearbeiten');
        $breadcrumb .= '</a></li>';

        return $this->render('projekte_uebersicht/anlegen.html.twig', [
            'headline'   => $this->translator->trans( 'projekt.bearbeiten' ) . ' ' . $shipdataObject->getName(),
            'form'       => $form->createView(),
            'projekt_id' => $id,
            'breadcrumb' => $breadcrumb,
            'shipdata'   => $shipdataObject,

        ]);
    }

    /**
     * @param int                    $id
     * @param NetShipdataRepository  $shipdataReposetory
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @return Response
     */
    #[Route('/hafenliste/{id}', name: 'hafen_liste')]
    public function hafenListe( int $id, NetShipdataRepository  $shipdataReposetory, TranslatorInterface $translator ): Response
    {
        $shipdataObject = $shipdataReposetory->find( $id );

        $breadcrumbs = [ [ 'label' => 'breadcrumb.home', // Translation key
                           'route' => 'app_home'         // Route name for the link
                         ],
                         [ 'label' => 'breadcrumb.projects_overview',
                           'route' => 'projekte_uebersicht_list'
                         ],
                         [ 'label'       => 'breadcrumb.project_detail', // Key for "Projekt %name%"
                           'params'      => [ '%name%' => $shipdataObject->getName() ], // Parameters for translation
                           'route'       => 'projekt_deails',
                           'routeParams' => [ 'id' => $shipdataObject->getId() ] // Parameters for the route
                         ],
                         [ 'label' => 'breadcrumb.hafen_liste' // Current page (no link)
                         ]
        ];
        //dd($shipdataObject->getPastPortVisits());
        return $this->render( 'projekte_uebersicht/hafenliste.html.twig', [ 'headline'    => $translator->trans( 'Häfen von' )
                                                                                             . ' '
                                                                                             . $shipdataObject->getName(),
                                                                            'projekt_id'  => $id,
                                                                            'breadcrumbs' => $breadcrumbs,
                                                                            'shipdata'    => $shipdataObject,
                                                                            'auswertung' => $this->analysiereHafenbesuche($shipdataObject->getPastPortVisits())

        ] );
    }

    /**
     * @param int                    $id
     * @param NetShipdataRepository  $shipdataReposetory
     * @param Request                $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface    $translator
     * @return Response
     */
    #[Route('/navliste/{id}', name: 'nav_liste')]
    public function navListe( int $id,Request $request, NetShipdataRepository  $shipdataReposetory, TranslatorInterface $translator ): Response
    {
        $shipdataObject = $shipdataReposetory->find( $id );
        $months         = $request->query->getInt('months', 6);
        $timePart       = new \DateTimeImmutable("-" . $months . " months");
        $positions      = $this->em->getRepository(NetShipPositionHistory::class)->findPathForShip( $id, $timePart );

        $path = array_map(fn($p) => [
            (float)$p->getLatitude(),
            (float)$p->getLongitude(),
            $p->getTimestamp()->format('d.m.Y H:i'),
            $p->getSpeed() . ' kn',
            $p->getCourse() . '°',
        ], $positions);

        $breadcrumbs = [
            [ 'label' => 'breadcrumb.home', // Translation key
                           'route' => 'app_home'         // Route name for the link
                         ],
            [ 'label' => 'breadcrumb.projects_overview',
                           'route' => 'projekte_uebersicht_list'
                         ],
            [ 'label'       => 'breadcrumb.project_detail', // Key for "Projekt %name%"
                           'params'      => [ '%name%' => $shipdataObject->getName() ], // Parameters for translation
                           'route'       => 'projekt_deails',
                           'routeParams' => [ 'id' => $shipdataObject->getId() ] // Parameters for the route
                         ],
            [ 'label' => 'breadcrumb.hafen_liste' // Current page (no link)
                         ]
        ];

        return $this->render( 'projekte_uebersicht/track.html.twig', [
            'headline'       => $translator->trans( 'Gefahrene Strecke ' ) . ' ' . $shipdataObject->getName(),
            'projekt_id'     => $id,
            'breadcrumbs'    => $breadcrumbs,
            'shipdata'       => $shipdataObject,
            'ship'           => $shipdataObject,
            'selectedMonths' => $months,
            'pathDataCount'  => count($path),
            'pathJson'       => json_encode($path)
        ] );
    }

    #[Route('/anlegen', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_anlegen')]
    #[IsGranted('ROLE_USER')]
    public function anlegen(Request $request, EntityManagerInterface $em): Response
    {
        $shipdataObject = new NetShipdata;
        $form = $this->createForm(NetShipdataType::class, $shipdataObject);
        $form->handleRequest($request);

        if($form->isSubmitted() )//&& $form->isValid())
        {
            /** @var UploadedFile $anlage */
            $anlage = $form->get('Schiffsbild')->getData();
            if($anlage)
            {
                $destination = $this->getParameter( 'anlagenShipOrdner' );
                $dateiname = md5( uniqid() ) . '.' . $anlage->guessClientExtension();
                // Pfad zur temporären Datei (direkt nach dem Upload)
                $tempPath  = $anlage->getRealPath();
                $finalPath = $destination . '/' . $dateiname;

                // Bild Kompression aufrufen
                $this->compressAndScaleImage($tempPath, $finalPath);
                $shipdataObject->setPic($dateiname);
            }
            $shipdataObject->setAisUpdate( 0 )
                           ->setStatus( $this->fetchStatusAktiv() );

            $em->persist($shipdataObject);
            $em->flush();

            $this->addFlash('success', 'Das Projekt wurde erfolgreich angelegt.');

            return $this->redirectToRoute( 'projekt_deails', [ 'id' => $shipdataObject->getId()]);
        }
        elseif ($form->isSubmitted() && !$form->isValid())
        {
            // --- DEBUGGING-TEIL ---
            // Das Formular wurde gesendet, war aber UNGÜLTIG.
            $errors = $form->getErrors(true, true);
            $errorString = (string) $errors;

            // Zeige den Fehler an.
            $this->addFlash('error', 'DEBUG (Formular ungültig): ' . $errorString);
        }

        $breadcrumb  = '<li class="breadcrumb-item"><a href="/">';
        $breadcrumb .= $this->translator->trans('Home');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekte_uebersicht_list') . '">Projekte Übersicht</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Projekt anlegen</a></li>';

        return $this->render('projekte_uebersicht/anlegen.html.twig', [
            'headline'   => 'Projekt anlegen',
            'form'       => $form->createView(),
            'breadcrumb' => $breadcrumb,
            'projekt_id' => null,
            'shipdata'   => null,
        ]);
    } 
            
    #[Route('/entfernen/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_entfernen')]
    #[IsGranted('ROLE_USER')]
    public function entfernen(int $id, NetShipdataRepository $shipdataReposetory, EntityManagerInterface $em): Response
    {
        try {
            $shipdataObject = $shipdataReposetory->find($id);
            $em->remove($shipdataObject);
            $em->flush();

            $this->addFlash('success', 'Das Projekt wurde erfolgreich entfernt.');
        }
        catch (\Exception $exception){

            $this->addFlash('warning', 'Fehler: Aktion NICHT möglich ' . $exception->getMessage());
        }

        return $this->redirect($this->generateUrl('projekte_uebersicht_list'));
    }

    #[Route('/zumarchiv/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_zum_archiv')]
    #[IsGranted('ROLE_USER')]
    public function zumArchiv(int $id, NetShipdataRepository $shipdataReposetory, EntityManagerInterface $em): Response
    {
        $shipdataObject = $shipdataReposetory->find($id);
        $statusObj = $em->getRepository(NetProjektStatus::class)->findOneBy(['bezeichnung' => 'archiv']);
        $this->updateStatus($shipdataObject, $statusObj, $em);

        $em->persist($shipdataObject);
        $em->flush();

        $message = 'Das Projekt "' . $shipdataObject->getName() . '" wurde erfolgreich ins Archiv verschoben.';
        $this->addFlash('success', $message);

        return $this->redirect($this->generateUrl('projekte_uebersicht_list'));
    }

    #[Route('/vonarchiv/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_von_archiv')]
    #[IsGranted('ROLE_USER')]
    public function vonArchiv(int $id, NetShipdataRepository $shipdataReposetory, EntityManagerInterface $em): Response
    {
        $shipdataObject = $shipdataReposetory->find($id);
        $statusObj = $em->getRepository(NetProjektStatus::class)->findOneBy(['bezeichnung' => 'check']);
        $this->updateStatus($shipdataObject, $statusObj, $em);

        $em->persist($shipdataObject);
        $em->flush();

        $message = 'Das Projekt "' . $shipdataObject->getName() . '" wurde erfolgreich aus dem Archiv geholt.';
        $this->addFlash('success', $message);

        return $this->redirect($this->generateUrl('projekte_uebersicht_list'));
    }

    #[Route('/porttime/add', methods: ['POST'], name: 'projekt_porttime_add')]
    #[IsGranted('ROLE_USER')]
    public function porttimeAdd(Request $request, NetShipdataRepository $shipdataReposetory, EntityManagerInterface $em): Response
    {
        $portTime  = $request->get('net_projekt_port_time');
        $shipdataObject = $shipdataReposetory->find($portTime['shipdataId']);
        $netShipdataPort = new NetShipdataPortLog();
        $netShipdataPort->setShipdata($shipdataObject)
            ->setPort($em->getRepository(NetPort::class)->find($portTime['port']))
            ->setEventTimestamp(new \DateTimeImmutable($portTime['arrival']))
            ->setEventType('ARRIVAL');
        $em->persist($netShipdataPort);
        if(isset($portTime['departure']))
        {
            $netShipdataPort = new NetShipdataPortLog();
            $netShipdataPort->setShipdata($shipdataObject)
                            ->setPort($em->getRepository(NetPort::class)->find($portTime['port']))
                            ->setEventTimestamp(new \DateTimeImmutable($portTime['departure']))
                            ->setEventType('DEPARTURE');
            $em->persist($netShipdataPort);
        }

        $em->flush();

        $message = 'Dem Projekt "' . $shipdataObject->getName() . '" wurde erfolgreich der Hafen ' . $netShipdataPort->getPort()->getBezeichnung() . ' hinzugefügt.';
        $this->addFlash('success', $message);

        return $this->redirectToRoute( 'projekt_deails', [ 'id' => $shipdataObject->getId()]);
    }

    #[Route('/projekt/{id}/toggle-ais', name: 'projekt_toggle_ais', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleAisUpdate(int $id, EntityManagerInterface $entityManager): Response
    {
        $ship = $entityManager->getRepository(NetShipdata::class)->find($id);

        if (!$ship) {
            throw $this->createNotFoundException('Schiff nicht gefunden');
        }

        $currentStatus = $ship->isAisUpdate();
        $ship->setAisUpdate(!$currentStatus);

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'newStatus' => $ship->isAisUpdate(),
            'message' => 'AIS-Update-Status erfolgreich umgeschaltet.'
        ]);
    }

    #[Route('/ship/{id}/update-position', name: 'ship_update_position', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function manualUpdatePosition( Request $request, NetShipdata $ship, VesselFinderService $vesselFinderService,  TranslatorInterface $translator ): Response
    {
        // CSRF-Token validieren
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('update-position-'.$ship->getId(), $submittedToken))
        {
            $this->addFlash('error', $translator->trans('flash.invalid_request'));
            return $this->redirectToRoute('projekt_deails', ['id' => $ship->getId()]);
        }

        try
        {
            $updated = $vesselFinderService->fetchAndUpdateSingleShipPosition( $ship );
            if ( $updated )
            {
                $this->addFlash( 'success', $translator->trans( 'flash.position_update.success', [ '%ship%' => $ship->getName() ] ) );
            }
            else
            {
                $this->addFlash( 'info', $translator->trans( 'flash.position_update.no_change', [ '%ship%' => $ship->getName() ] ) );
            }
        }
        catch ( \Exception $e )
        {
            $this->addFlash( 'error', $translator->trans( 'flash.error_occurred', [ '%error%' => $e->getMessage() ] ) );
        }

        $this->addFlash( 'success', 'Positions Update wurde durchgeführt.' );

        // Zurück zur Detailseite leiten
        return $this->redirectToRoute('projekt_deails', ['id' => $ship->getId()]);
    }

    #[Route('/baugruppenliste/{id}', methods: ['GET', 'POST'], name: 'projekt_baugruppen_list')]
    public function projektBaugruppenList(int $id, NetKomponentenRepository $komponnetenReposetory, NetShipdataRepository $shipdataReposetory ): Response
    {
        $shipdataObject    = $shipdataReposetory->find($id);
        $komponnetenObject = $komponnetenReposetory->findProjektBy($id);
        $suche             = '';
        // 2. NEU: Aggregations-Array
        $baugruppenSummary = [];

        // 3. Wir iterieren über die flache Liste und bauen das Summary-Array auf
        foreach ($komponnetenObject as $komponente) {

            // Wir brauchen einen eindeutigen Key (die ID der Baugruppen-Art)
            // Wenn keine Baugruppe gesetzt ist, überspringen
            if ($komponente->getBaugruppe() === null) {
                continue;
            }

            $key = $komponente->getBaugruppe()->getId();

            // 4. Wenn die Baugruppe noch nicht im Summary ist, initialisieren wir sie
            if (!isset($baugruppenSummary[$key])) {
                $baugruppenSummary[$key] = [
                    'objekt' => $komponente->getBaugruppe(), // Das Baugruppen-Objekt
                    'anzahl' => 0, // Zähler für die Instanzen (Lfnr)
                    'summePreis' => 0.0,
                    'summeGewicht' => 0.0,
                    'lfnr_list' => [] // Hilfsvariable, um Instanzen zu zählen
                ];
            }

            // 5. Preis- und Gewichts-Berechnungen (wie in Twig)
            $menge = $komponente->getMenge() ?? 1;
            $artikel = $komponente->getArtikel();

            if ($artikel && $artikel->getEkBrutto() !== null) {
                $komponentenPreis = $menge * $artikel->getEkBrutto();
                $baugruppenSummary[$key]['summePreis'] += $komponentenPreis;
            }

            if ($artikel && $artikel->getMasse() !== null) {
                $komponentenGewicht = $menge * ($artikel->getMasse() / 1000); // in kg
                $baugruppenSummary[$key]['summeGewicht'] += $komponentenGewicht;
            }

            // 6. Zählen der Instanzen (Anzahl)
            // Wir fügen die Lfnr hinzu, um sie eindeutig zu zählen
            $lfnr = $komponente->getBaugruppeLfnr();
            if (!in_array($lfnr, $baugruppenSummary[$key]['lfnr_list']))
            {
                $baugruppenSummary[$key]['lfnr_list'][] = $lfnr;
                $baugruppenSummary[$key]['anzahl']++; // Erhöhe die Anzahl der Instanzen
            }
        }

        $headline  = '' . $shipdataObject->getName();
        if($shipdataObject->getImo() != '0')
        {
            $headline .= ' - ' . $shipdataObject->getImo();
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.projects_overview',
                'route' => 'projekte_uebersicht_list'
            ],
            [
                'label' => 'breadcrumb.project_detail', // Key for "Projekt %name%"
                'params' => ['%name%' => $shipdataObject->getName()], // Parameters for translation
                'route' => 'projekt_deails',
                'routeParams' => ['id' => $shipdataObject->getId()] // Parameters for the route
            ],
            [
                'label' => 'breadcrumb.project_componentsgoups' // Current page (no link)
            ]
        ];

        return $this->render('projekt_komponenten/uebersichtbaugruppen.html.twig', [
            'headline'    => $headline,
            'breadcrumbs'  => $breadcrumbs,
            'komponenten' => $komponnetenObject,
            'baugruppenSummary' => $baugruppenSummary,
            'shipdata'    => $shipdataObject,
            'suchbegriff' => $suche,
            'projekt_id' => $id
        ]);
    }

    #[Route( '/{id}/add-bay', name: 'projekt_add_single_bay', methods: [ 'POST' ] )]
    public function addBay( int $id, Request $request, NetShipdataRepository  $shipRepo, EntityManagerInterface $em ): Response
    {
        $ship = $shipRepo->find( $id );
        $bayNr = $request->request->getInt( 'new_bay_nr' );
        $with20ft = $request->request->get( 'with_20ft' ) === '1';

        // 1. Haupt-Bay anlegen
        $mainBay = new NetBayStructure();
        $mainBay->setBayNumber( $bayNr );
        $mainBay->setIsEven( true );
        $mainBay->setShipdata( $ship );
        $em->persist( $mainBay );

        // 2. Optional: 20' Kinder (nur wenn gewünscht)
        if ( $with20ft )
        {
            foreach ( [ $bayNr - 1, $bayNr + 1 ] as $subNr )
            {
                $sub = new NetBayStructure();
                $sub->setBayNumber( $subNr );
                $sub->setIsEven( false );
                $sub->setParentBay( $mainBay );
                $sub->setShipdata( $ship );
                $em->persist( $sub );
            }
        }

        $em->flush();

        return $this->redirectToRoute( 'projekt_bearbeiten', [ 'id' => $id ] );
    }

    #[Route('/delete-bay/{bayId}', name: 'projekt_delete_single_bay', methods: ['POST'])]
    public function deleteSingleBay(int $bayId, NetBayStructureRepository $bayRepo, EntityManagerInterface $em): Response
    {
        $bay = $bayRepo->find( $bayId );
        if ( ! $bay )
        {
            return $this->redirectToRoute( 'projekte_uebersicht_list' ); // Oder Fehlermeldung
        }

        $shipId = $bay->getShipdata()
                      ->getId();

        // Falls es eine 40' Bay ist, löschen wir erst die Kinder
        $em->createQuery( 'DELETE FROM App\Entity\NetBayStructure b WHERE b.parent_bay = :parent' )
           ->setParameter( 'parent', $bay )
           ->execute();

        $em->remove( $bay );
        $em->flush();

        $this->addFlash( 'info', 'Bay wurde entfernt.' );

        return $this->redirectToRoute( 'projekt_bearbeiten', [ 'id' => $shipId ] );
    }

    #[Route('/{id}/clear-bays', name: 'projekt_clear_bays', methods: ['POST'])]
    public function clearBays(int $id, EntityManagerInterface $em): Response
    {
        // 1. Zuerst alle Kinder löschen (20' Bays)
        $em->createQuery( 'DELETE FROM App\Entity\NetBayStructure b WHERE b.shipdata = :id AND b.parent_bay IS NOT NULL' )
           ->setParameter( 'id', $id )
           ->execute();

        // 2. Dann alle Eltern löschen (40' Bays)
        $em->createQuery( 'DELETE FROM App\Entity\NetBayStructure b WHERE b.shipdata = :id' )
           ->setParameter( 'id', $id )
           ->execute();

        $this->addFlash( 'success', 'Gesamte Bay-Struktur wurde gelöscht.' );

        return $this->redirectToRoute( 'projekt_bearbeiten', [ 'id' => $id ] );
    }

    #[Route('/ais/map', name: 'projekt_ais_map')]
    public function map(NetShipdataRepository $repo): Response
    {
        $ships = $repo->findLatestPositions();

        foreach ( $ships as &$ship )
        {
            $ship['detailUrl'] = $this->generateUrl('projekt_deails', ['id' => $ship['shipId']]);
            if ( $ship['timestamp'] instanceof \DateTimeInterface )
            {
                $ship['formattedDate'] = $ship['timestamp']->format( 'd.m.Y H:i' );
            }
            else if ( isset( $ship['timestamp']['date'] ) )
            {
                // Falls es schon ein Array ist
                $date = new \DateTime( $ship['timestamp']['date'] );
                $ship['formattedDate'] = $date->format( 'd.m.Y H:i' );
            }
            // Formatierung Ankunftszeit (ETA)
            if ( $ship['eta'] instanceof \DateTimeInterface )
            {
                $ship['formattedEta'] = $ship['eta']->format( 'd.m.Y H:i' );
            }
            elseif ( is_array( $ship['eta'] ) && isset( $ship['eta']['date'] ) )
            {
                $ship['formattedEta'] = ( new \DateTime( $ship['eta']['date'] ) )->format( 'd.m.Y H:i' );
            }
            else
            {
                $ship['formattedEta'] = 'keine Angabe';
            }

            // Typ-Logik für die Farbe
            $ship['color'] = match ( $ship['type'] ?? '' )
            {
                'Vehicles Carrier' => 'yellow',   // Yellow
                'Military ops'     => '#095301',  // Grün
                'Tanker'           => '#cc0000',  // Rot
                'Frachter'         => '#ff9900',  // Orange
                'Containerschiff'  => '#1a53ff',  // Blau
                default            => '#555555',  // Grau
            };
        }
        unset($ship);

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.projects_overview',
                'route' => 'projekte_uebersicht_list'
            ],
            [
                'label' => 'Übersicht Karte',
                'route' => 'projekt_ais_map'
            ]
        ];

        return $this->render('projekte_uebersicht/ais_map.html.twig', [
            'headline'   => $this->translator->trans('Projektübersicht Karte'),
            'breadcrumbs'=> $breadcrumbs,
            'ships_json' => json_encode($ships),
            'shipcount'  => count($ships)
        ]);
    }

    /**
     * @param $besuchteHaefen
     * @return array|array[]
     */
    public function analysiereHafenbesuche( $besuchteHaefen )
    {
        $auswertung = [ 'hafen' => [],
                        'land'  => []
        ];

        foreach ( $besuchteHaefen as $hafen )
        {
            /** @var NetPort $port */
            $port = $hafen['port'];
            $hafenName = $port->getBezeichnung();
            $landName = $port->getFlag()->getAmtlicheKurzform();

            // Überspringe, wenn Daten unvollständig sind
            if ( ! $hafenName || ! $landName )
            {
                continue;
            }

            // Hafen zählen
            if ( ! isset( $auswertung['hafen'][ $hafenName ] ) )
            {
                $auswertung['hafen'][ $hafenName ] = 0;
            }
            $auswertung['hafen'][ $hafenName ]++;

            // Land zählen
            if ( ! isset( $auswertung['land'][ $landName ] ) )
            {
                $auswertung['land'][ $landName ] = 0;
            }
            $auswertung['land'][ $landName ]++;
        }

        // NEU: Sortiere beide Listen alphabetisch nach dem Schlüssel (Name)
        ksort( $auswertung['hafen'] );
        arsort( $auswertung['land'] );

        return $auswertung;
    }

    public function updateStatus(NetShipdata $schiff, NetProjektStatus $newStatus, EntityManagerInterface $em)
    {
        $schiff->setStatus($newStatus);

        $em->flush();
    }

    private function fetchStatusAktiv():NetProjektStatus
    {
        return $this->em->getRepository( NetProjektStatus::class )
                        ->findOneBy( [ 'bezeichnung' => 'aktiv' ] );
    }
}

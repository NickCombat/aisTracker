<?php
// AisStreamService
namespace App\Service;

use WebSocket\Client;
use WebSocket\ConnectionException;
use App\Repository\NetShipdataRepository;
use App\Entity\NetShipPositionHistory;
use Symfony\Component\HttpKernel\KernelInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\NetShipdata;
use App\Entity\NetShipNavStatus;
use App\Entity\NetPort;
use App\Entity\Flaggenstaaten;
use App\Entity\NetShipdataPortLog;
use App\Entity\NetShipTyp;
use App\Entity\AisStreamStatus;
use Symfony\Contracts\Cache\CacheInterface;
use Doctrine\Persistence\ManagerRegistry;

class AisStreamService extends addService
{

    protected string $rawLogPath;

    private int $lastMmsiUpdate;

    private int $messageCount = 0;

    public function __construct(
        private readonly    SettingsService     $settings,
        protected readonly  KernelInterface     $kernel,
        protected readonly  LoggerInterface     $logger,
        protected readonly  RequestStack        $requestStack,
        private readonly NetShipdataRepository  $shipRepository,
        private             CacheInterface      $cache,
        private             ManagerRegistry     $registry
    )
    {
        // 1. Zuerst den EntityManager initialisieren!
        $this->registry = $registry;
        $this->entityManager = $registry->getManager();
        // 2. Logischer Fehler behoben: ?? statt ||
        $this->apiKey     = $this->settings->get( 'aisstream.api.key' ) ?? null;
        $this->rawLogPath = $this->kernel->getProjectDir() . '/var/log/aisstream_raw/';
        // 3. Jetzt kann der EntityManager sicher verwendet werden
        $this->statusRepository = $this->entityManager->getRepository(AisStreamStatus::class)->findLastEntry();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function listen(): void
    {
        try
        {
            $lockFile = $this->kernel->getProjectDir() . '/var/log/ais_stream.lock';
            $this->rawLogPath = $this->kernel->getProjectDir() . '/var/log/aisstream_raw/';
            touch( $lockFile );

            $currentMmsis = $this->fetchShipMMSI();
            $this->lastMmsiUpdate = time();

            $apiKey = $this->settings->get( 'aisstream.api.key' );
            $rawBoundingBoxes = $this->settings->get( 'aisstream.api.BoundingBoxes' );

            if ( is_string( $rawBoundingBoxes ) )
            {
                $boundingBoxes = json_decode( $rawBoundingBoxes, true );
                if ( json_last_error() !== JSON_ERROR_NONE )
                {
                    $this->logger->error( sprintf( "AISStream: BoundingBoxes JSON-Fehler [%s]. Rohdaten: %s", json_last_error_msg(), $rawBoundingBoxes ) );
                    $boundingBoxes = null;
                }
            }
            else
            {
                $boundingBoxes = $rawBoundingBoxes;
            }

            if ( empty( $boundingBoxes ) || ! is_array( $boundingBoxes ) )
            {
                $this->logger->warning( "AISStream: Nutze globalen BoundingBox-Fallback." );
                $boundingBoxes = [ [ [ -90, -180 ], [ 90, 180 ] ] ];
            }

            $this->entityManager->getConnection()
                                ->getConfiguration()
                                ->setMiddlewares( [] );
            $options = [ 'timeout' => 60, 'fragment_size' => 65536 ];

            $subscribeData = [ "APIKey"        => $apiKey,
                               "BoundingBoxes" => $boundingBoxes
            ];
            if ( ! empty( $currentMmsis ) )
            {
                $subscribeData["FiltersShipMMSI"] = $currentMmsis;
            }

            while ( true )
            {
                try
                {
                    $client = new Client( "wss://stream.aisstream.io/v0/stream", $options );
                    $client->setTimeout( 60 );

                    $subscribeMessage = json_encode( $subscribeData );
                    $this->logger->critical( "Sende an AISStream: " . $subscribeMessage );
                    $client->text( $subscribeMessage );

                    // Bei erfolgreichem Connect: Status auf 'online'
                    $this->updateStatus( 'online', 'Verbunden' );

                    while ( true )
                    {
                        $suffix = '';
                        if ( ! file_exists( $lockFile ) )
                        {
                            $this->logger->warning( "Lock-File fehlt. Beende AisStreamService regulär." );
                            break 2;
                        }

                        if ( time() - $this->lastMmsiUpdate > 1800 )
                        {
                            $subscribeMessage = json_encode( $subscribeData );
                            $client->text( $subscribeMessage );
                            $this->lastMmsiUpdate = time();
                        }

                        try
                        {
                            $message = $client->receive();

                            // Aktualisiert nur das Timestamp für 'online'
                            $this->updateStatus( 'online' );

                            if ( ! $message )
                            {
                                continue;
                            }

                            $data = json_decode( $message, true );
                            $mmsi = $data['MetaData']['MMSI'] ?? null;
                            if ( ! $mmsi || $mmsi === '0' )
                            {
                                continue;
                            }
                            $suffix .= '_mmsi' . $mmsi . '_' . $data['MessageType'];

                            $ship = $this->entityManager->getRepository( NetShipdata::class )
                                                        ->findOneBy( [ 'MMSI' => $mmsi ] );
                            if ( ! $ship || $ship->getStatus() === 2 )
                            {
                                continue;
                            }

                            if ( in_array( $data['MessageType'], [ 'PositionReport',
                                                                   //'ShipStaticData',
                                                                   //'Interrogation',
                                                                   //'UnknownMessage',
                                                                   //'DataLinkManagementMessage',
                                                                   'StandardClassBPositionReport'
                            ] ) )
                            {
                                $this->savePosition( $data, $ship );
                                fwrite( STDERR, date( "Y.m.d H:i:s" ) . ' ' . $data['MessageType'] . ' ' . $mmsi );
                            }

                            if ( $data['MessageType'] === 'ShipStaticData' )
                            {
                                $this->updateShipData( $data, $ship );
                                $this->saveDestination( $data, $ship );
                                fwrite( STDERR, date( "Y.m.d H:i:s" ) . ' ' . $data['MessageType'] . ' ' . $mmsi );
                            }

                            $this->saveRawData( json_encode( $data ), $suffix );
                            $this->cleanupMemory();
                        }
                        catch ( \WebSocket\TimeoutException $e )
                        {
                            $client->ping();
                            continue;
                        }
                    }
                }
                catch ( ConnectionException $e )
                {
                    $errorMessage = "Verbindungsabbruch (TCP Timeout) - Verbinde neu...";
                    $this->logger->info( $errorMessage );

                    $this->updateStatus( 'offline', $errorMessage );
                    sleep( 15 );
                }
                catch ( \Throwable $e )
                {
                    $errorMsg = sprintf( "Fehler [%s] in %s:%d - %s", get_class( $e ), $e->getFile(), $e->getLine(), $e->getMessage() );
                    $this->logger->error( $errorMsg );

                    $this->updateStatus( 'error', $errorMsg );

                    if ( isset( $client ) )
                    {
                        $client->close();
                    }

                    if ( str_contains( $e->getMessage(), 'Empty read' ) || str_contains( $e->getMessage(), 'Timeout' ) )
                    {
                        sleep( 5 );
                        $this->cleanupMemory();
                        continue;
                    }

                    sleep( 15 );
                }
            }
        }
        catch ( \Exception $e )
        {
            $this->logger->critical( "FATAL STARTUP ERROR: " . $e->getMessage() );
            $this->updateStatus( 'error', "FATAL: " . $e->getMessage() );
            exit( 1 );
        }
    }

    /**
     * @param string $rawDestination
     * @return NetPort
     */
    private function createNewPort( string $rawDestination ): NetPort
    {
        $locodeCandidate = strtoupper(str_replace(' ', '', $rawDestination));
        $landKuerzel     = 'xx'; // Standard-Fallback für "Unbekannt"

        if (strlen($locodeCandidate) === 5 && preg_match('/^[A-Z]{2}[A-Z0-9]{3}$/', $rawDestination))
        {
            $landKuerzel = strtolower(substr($locodeCandidate, 0, 2));
        }
        else
        {
            // Optionale Logik: Hier könnte man eine Liste bekannter Namen abgleichen
            // Oder man lässt es bei 'xx' und pflegt den Port später manuell im Admin-Panel nach.
            $this->logger->info("Klarnamen-Hafen erkannt: " . $rawDestination);
        }

        $flagge = $this->entityManager->getRepository(Flaggenstaaten::class)
                                      ->findOneBy(['kuerzel' => $landKuerzel]);

        $port = new NetPort();
        $port->setKuerzel( substr($rawDestination,0,6) )
             ->setFlag( $flagge )
             ->setBezeichnung( $rawDestination ?? '-NA-' )
             ->setLand( $landKuerzel );

        $this->entityManager->persist( $port );
        $this->entityManager->flush();
        $this->entityManager->refresh($port);

        $this->logOrFlash( 'notice', 'New port added: (LOCODE: ' . $rawDestination . ')' );

        return $port;
    }

    private function saveDestination( mixed $data, NetShipdata $ship ):void
    {
        $shipStaticData = $data['Message']['ShipStaticData'];
        // Validierung (IMO)
        //if ( 0 !== $shipStaticData['ImoNumber'] && $shipStaticData['ImoNumber'] !== $ship->getImo() )
        //{
        //    $message = 'Falsche Hafendaten für ' . $ship->getName() . ' ';
        //    if ( $ship->getImo() )
        //    {
        //        $message .= '(IMO: ' . $ship->getImo() . ') ';
        //    }
        //    elseif ( $ship->getMMSI() )
        //    {
        //        $message .= '(mmsi: ' . $ship->getMMSI() . ') ';
        //    }
        //    $message .= '[' . $shipStaticData['ImoNumber'] . '] ';
        //
        //    $message .= 'empfangen.';
        //
        //    $this->logOrFlash( 'warning', $message );
        //
        //    return;
        //}

        // Port-Handling (Robustere Erkennung)
        $rawDestination = $this->cleanUpDestination( $shipStaticData['Destination'] );
        if (empty($rawDestination) || $rawDestination === '@@@@@@@@')
        {
            $this->logOrFlash( 'warning', 'Kein Ziel für ' . $ship->getName() . ' (mmsi: ' . $ship->getMMSI() . ') angegeben.' );

            return;
        }

        // 1. Versuch: Suche exakt nach LOCODE
        $port = $this->entityManager->getRepository( NetPort::class )
                                    ->findOneBy( [ 'kuerzel' => $rawDestination ] );
        // 2. Versuch: Falls nicht gefunden, suche nach Name (Klarnamen-Logik)
        if (null === $port)
        {
            $port = $this->entityManager->getRepository(NetPort::class)
                                        ->findOneBy(['bezeichnung' => $rawDestination]);
        }
        // 3. Fallback: Neuen Hafen anlegen mit intelligenter Land-Erkennung
        if (null === $port)
        {
            $port = $this->createNewPort($rawDestination);
        }
        if (!$this->entityManager->contains($port))
        {
            $port = $this->entityManager->find(NetPort::class, $port->getId());
        }

        // Intelligentes ETA-Jahr
        $etaMonth = (int)$shipStaticData['Eta']['Month'];
        $etaDay   = (int)$shipStaticData['Eta']['Day'];

        if ($etaMonth === 0 || $etaDay === 0)
        {
            return; // Ungültige ETA
        }

        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('n');

        // Wenn ETA im Januar/Februar liegt, wir aber im Dezember sind -> nächstes Jahr
        $year = ($etaMonth < $currentMonth && $currentMonth >= 11) ? $currentYear + 1 : $currentYear;

        $timestampStr = sprintf('%04d-%02d-%02d %02d:%02d:00',
            $year, $etaMonth, $etaDay,
            $shipStaticData['Eta']['Hour'], $shipStaticData['Eta']['Minute']
        );

        try
        {
            $apiTimestamp = new \DateTimeImmutable($timestampStr);
        }
        catch (\Exception $e)
        {
            return; // Ungültiges Datum abfangen
        }

        $today = new \DateTimeImmutable( 'today', $apiTimestamp->getTimezone() );
        if ( $apiTimestamp < $today )
        {
            return;
        }

        // 4. Duplicate Check & Log
        $logRepository = $this->entityManager->getRepository(NetShipdataPortLog::class);

        $existingLog = $logRepository->findOneBy([
            'shipdata'       => $ship->getId(),
            'eventTimestamp' => $apiTimestamp,
            'eventType'      => 'ARRIVAL'
        ]);

        if (null === $existingLog)
        {
            $history = new NetShipdataPortLog();
            $history->setShipdata($ship);
            $history->setEventTimestamp($apiTimestamp);
            $history->setPort($port);
            $history->setEventType('ARRIVAL');

            $this->entityManager->persist($history);
        }

        $qb = $logRepository->createQueryBuilder( 'log' );
        $departureLog = $qb->where( 'log.shipdata = :ship' )
                           ->andWhere( 'log.port = :port' )
                           ->andWhere( 'log.eventType = :type' )
                           ->andWhere( 'log.eventTimestamp >= :today' )
                           ->setParameter( 'ship', $ship->getId() )
                           ->setParameter( 'port', $port->getId() )
                           ->setParameter( 'type', 'DEPARTURE' )
                           ->setParameter( 'today', $today )
                           ->orderBy( 'log.eventTimestamp', 'ASC' )
                           ->setMaxResults( 1 )
                           ->getQuery()
                           ->getOneOrNullResult();

        if ( null === $departureLog )
        {
            $newDepartureLog = new NetShipdataPortLog();
            $newDepartureLog->setShipdata( $ship );
            $newDepartureLog->setPort( $port );
            $newDepartureLog->setEventType( 'DEPARTURE' );
            $newDepartureLog->setEventTimestamp($apiTimestamp->modify('+2 days'));

            $this->entityManager->persist($newDepartureLog);
        }

        //$this->processAisMessage($data, $ship);
    }

    private function savePosition( array $data, NetShipdata $ship ): void
    {
        $lastEntry = $this->entityManager->getRepository(NetShipPositionHistory::class)
                                         ->findOneBy(['netShipdata' => $ship], ['timestamp' => 'DESC']);
        if ( $lastEntry )
        {
            $lastTime = $lastEntry->getTimestamp();
            $now = new \DateTimeImmutable();

            $diffInMinutes = ( $now->getTimestamp() - $lastTime->getTimestamp() ) / 60;
            if ( $diffInMinutes >= 0 && $diffInMinutes < 30 )
            {
                $this->logger->info('Zeit zu kurz. return (' . $ship->getName() . ') ' . $now->getTimestamp() . '-' . $lastTime->getTimestamp() . ' ['.$diffInMinutes.'] ' . $data['MessageType']);
                return;
            }
        }

        $this->processAisMessage($data, $ship);
    }

    private function updateShipData( array $data, NetShipdata $ship ): void
    {
        $newData  = $data['Message']['ShipStaticData'];
        if($ship->getRufzeichen() !== $newData['CallSign'])
        {
            $ship->setRufzeichen( $newData['CallSign'] );
        }
        if($ship->getImo() !== $newData['ImoNumber'])
        {
            $ship->setImo( $newData['ImoNumber'] );
        }
        if($ship->getName() !== trim($newData['Name']))
        {
            $ship->setName( trim($newData['Name']) );
        }

        $this->entityManager->persist( $ship );
    }

    private function saveShipData( array $data ): void
    {
        $this->logger->debug( 'aisstream ' . $data['MetaData']['MMSI'] . ' NEU eingetragen' );

        $ship = new NetShipdata();
        $ship->setName( trim($data['MetaData']['ShipName']) )
             ->setMMSI( $data['MetaData']['MMSI'] )
             ->setStatus( $this->fetchStatusAktiv() )
             ->setImo( (int) ($data['Message']['ImoNumber'] ?? 0) )
             ->setAisUpdate(false);

        if ( isset($data['Message']['ShipStaticData']) && isset($data['Message']['ShipStaticData']['CallSign'] ))
        {
            $ship->setRufzeichen( $data['Message']['ShipStaticData']['CallSign'] );
        }

        if(isset($data['Message']['Type']))
        {
            $shipTyp = $this->fetchShipType( $data['Message']['Type'] );
            if ( $shipTyp )
            {
                $ship->setType( $shipTyp );
            }
        }
        $this->entityManager->persist($ship);
        $this->entityManager->flush();

        $this->processAisMessage( $data, $ship );
    }

    private function processAisMessage( array $data, $ship ): void
    {
        try
        {
            $history = new NetShipPositionHistory();
            $history->setNetShipdata( $ship );

            if ( in_array($data['MessageType'], ['PositionReport', 'StandardClassBPositionReport']) )
            {
                $pos = $data['Message']['PositionReport'];
                $history->setLatitude( $pos['Latitude'] );
                $history->setLongitude( $pos['Longitude'] );
                $history->setSpeed( $pos['Sog'] ); // Speed over Ground
                $history->setCourse( $pos['Cog'] ); // Course over Ground

                // Class B hat nicht immer einen NavigationalStatus, daher prüfen
                if ( isset($pos['NavigationalStatus']) )
                {
                    $navStatusCode = (int)$pos['NavigationalStatus'];
                    $navStatusEntity = $this->entityManager->getRepository( NetShipNavStatus::class )
                                                           ->findOneBy( [ 'status' => $navStatusCode ] );
                    if ( $navStatusEntity )
                    {
                        $history->setNavstat( $navStatusEntity );
                        $history->setZone( 'aisStream' );
                    }
                }
                $history->setTimestamp( new \DateTimeImmutable() );
            }

            if ( $data['MetaData'] )
            {
                if ( $data['MetaData']['latitude'] && null !== $data['MetaData']['latitude']
                     && $data['MetaData']['longitude']
                     && null !== $data['MetaData']['longitude']
                     && $data['MessageType'] !== 'PositionReport' )
                {
                    $history->setLatitude( $data['MetaData']['latitude'] );
                    $history->setLongitude( $data['MetaData']['longitude'] );
                }
                if ( $data['MetaData']['time_utc'] )
                {
                    $messageTimestamp = date( 'Y-m-dTH:i:s', strtotime( $data['MetaData']['time_utc'] ) );
                    $history->setTimestamp( new \DateTimeImmutable( $messageTimestamp ) );
                }
            }
            $this->entityManager->persist( $history );
            $this->entityManager->flush();
        }
        catch (\Throwable $e)
        {
            $this->logger->info('Fehler beim Log Speichern: ' . $e->getMessage() );
        }
    }

    private function fetchShipMMSI(): array
    {
        $shipsToUpdate = $this->shipRepository->findAll();
        $relevantMmsis = [];

        foreach ( $shipsToUpdate as $ship )
        {
            $mmsi = $ship->getMmsi();

            if ( $mmsi && (int)$mmsi !== 0 && preg_match('/^\d{9}$/', $mmsi) )
            {
                $relevantMmsis[] = (string)$mmsi;
            }
        }

        return array_values( array_unique( $relevantMmsis ) );
    }

    private function cleanupMemory(): void
    {
        $this->messageCount++;
        if ( $this->messageCount % 20 === 0 )
        {
            $this->entityManager->flush();
            $this->entityManager->clear();
            gc_collect_cycles(); // Erzwingt PHP-Müllabfuhr
            $this->logger->debug( "Memory Cleanup: " . round( memory_get_usage() / 1024 / 1024, 2 ) . " MB" );
        }
    }

    private function fetchShipType( $shipType )
    {
        $netShipType = $this->entityManager->getRepository(NetShipTyp::class);
        return $netShipType->findOneBy( [ 'name' => $shipType ] );
    }

    /**
     * Hilfsmethode: Speichert den aktuellen Status in der Datenbank
     */
    private function updateStatus( string $state, ?string $message = null ): void
    {
        // 1. Prüfen, ob der Manager noch offen ist. Wenn nicht: Reset.
        if ( ! $this->entityManager->isOpen() )
        {
            $this->entityManager = $this->registry->resetManager();
            $this->logger->info('EntityManager wurde nach einem Fehler zurückgesetzt.');
        }

        try
        {
            $statusRepo = $this->entityManager->getRepository( AisStreamStatus::class );
            // Versuche den letzten Eintrag zu holen, sonst lege neuen an
            $status = method_exists( $statusRepo, 'findLastEntry' ) ? $statusRepo->findLastEntry() : null;

            if ( ! $status )
            {
                $status = new AisStreamStatus();
            }

            $status->setStatus( $state );

            $status->setMessage( $message ?? '' );

            $status->setUpdatedAt( new \DateTimeImmutable() );

            $this->entityManager->persist( $status );
            $this->entityManager->flush();
        }
        catch ( \Throwable $e )
        {
            $this->logger->error( "Fehler beim Aktualisieren des AIS-Status: " . $e->getMessage() );
        }
    }
}
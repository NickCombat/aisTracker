<?php
// AisStreamService
namespace App\Service;

use WebSocket\Client;
use Doctrine\ORM\EntityManagerInterface;
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

class AisStreamService extends addService
{

    private string $apiKey;
    //private string $apiUrl;

    protected string $rawLogPath;

    private array $currentMmsis;

    private int $lastMmsiUpdate;

    private int $messageCount = 0;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SettingsService        $settings,
        protected readonly KernelInterface      $kernel,
        protected readonly LoggerInterface      $logger,
        protected readonly RequestStack         $requestStack,
        private readonly NetShipdataRepository  $shipRepository
    )
    {
        $this->apiKey     = $this->settings->get( 'aisstream.api.key' ) || null;
        //$this->apiUrl     = "wss://stream.aisstream.io/v0/stream";
        $this->rawLogPath = $this->kernel->getProjectDir() . '/var/log/aisstream_raw/';
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function listen(): void
    {
        $lockFile = $this->kernel->getProjectDir() . '/var/log/ais_stream.lock';
        $this->rawLogPath = $this->kernel->getProjectDir() . '/var/log/aisstream_raw/';
        touch( $lockFile );

        $this->currentMmsis = $this->fetchShipMMSI();
        // Initialer Zeitstempel für das Update-Intervall
        $this->lastMmsiUpdate = time();

        if ( empty( $this->apiKey ) || $this->apiKey == '1' )
        {
            $this->apiKey = $this->settings->get( 'aisstream.api.key' );
        }

        // ABSOLUT WICHTIG: Logging für Langläufer deaktivieren
        $this->entityManager->getConnection()
                            ->getConfiguration()
                            ->setMiddlewares( [] );
        $this->entityManager->getConnection()
                            ->getConfiguration()
                            ->setSQLLogger( null );

        $options = [ 'timeout' => 30, 'fragment_size' => 65536 ];

        while ( true )
        {
            try
            {
                $client = new Client( "wss://stream.aisstream.io/v0/stream", $options );
                $subscribeMessage = json_encode( [ "APIKey"                  => $this->apiKey,
                                                   "BoundingBoxes"           => [ [ [ -90, -180 ], [ 90, 180 ] ] ],
                                                   "FiltersSpecificShipMMSI" => $this->currentMmsis
                ] );
                $client->text( $subscribeMessage );

                while ( true )
                {
                    $suffix  = '';

                    if ( ! file_exists( $lockFile ) )
                    {
                        break 2; // Beendet beide Schleifen
                    }

                    // Update-Check alle 30 Minuten
                    if ( time() - $this->lastMmsiUpdate > 1800 )
                    {
                        $this->currentMmsis = $this->fetchShipMMSI();
                        $subscribeMessage = json_encode( [ "APIKey"                  => $this->apiKey,
                                                           "BoundingBoxes"           => [ [ [ -90, -180 ],[ 90, 180 ] ] ],
                                                           "FiltersSpecificShipMMSI" => $this->currentMmsis
                        ] );
                        $client->text( $subscribeMessage );
                        $this->lastMmsiUpdate = time();
                    }

                    $message = $client->receive();
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
                    $suffix .= '_mmsi' . $mmsi;
                    $suffix .= '_' . $data['MessageType'];

                    // Schiff frisch laden (Identity Map Refresh nach clear())
                    $ship = $this->entityManager->getRepository( NetShipdata::class )
                                                ->findOneBy( [ 'MMSI' => $mmsi ] );
                    if ( ! $ship || $ship->getStatus() === 2 )
                    {
                        continue;
                    }

                    if ( $data['MessageType'] === 'PositionReport' )
                    {
                        $this->savePosition( $data, $ship );
                    }
                    elseif ( $data['MessageType'] === 'ShipStaticData' )
                    {
                        $this->saveDestination( $data, $ship );
                    }

                    $this->saveRawData( json_encode($data), $suffix);

                    $this->cleanupMemory();
                }
            }
            catch ( \Exception $e )
            {
                $errorMsg = sprintf(
                    "Fehler [%s] in %s:%d - %s",
                    get_class($e),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                );
                $this->logger->error($errorMsg);

                fwrite(STDERR, $errorMsg . PHP_EOL);

                if ( isset( $client ) )
                {
                    $client->close();
                }

                // Reconnect bei Netzwerkfehlern
                if ( str_contains( $e->getMessage(), 'Empty read' ) || str_contains( $e->getMessage(), 'Timeout' ) )
                {
                    sleep( 2 );
                    continue;
                }
                exit( 1 ); // Bei echten Fehlern beenden, Cronjob regelt den Neustart
            }
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
        if (0 !== $shipStaticData['ImoNumber'] && $shipStaticData['ImoNumber'] !== $ship->getImo())
        {
            $this->logOrFlash( 'warning', 'Falsche Hafendaten für ' . $ship->getName() . ' (IMO: ' . $ship->getImo() . ')empfangen. [' . $data['IMO'] . ']' );
            return;
        }

        // Port-Handling (Robustere Erkennung)
        $rawDestination = trim($shipStaticData['Destination']);
        if (empty($rawDestination) || $rawDestination === '@@@@@@@@')
        {
            $this->logOrFlash( 'warning', 'Kein Ziel für ' . $ship->getName() . ' (IMO: ' . $ship->getImo() . ') angegeben.' );
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

        $this->processAisMessage($data, $ship);
    }

    private function savePosition( array $data, NetShipdata $ship ): void
    {
        $lastEntry = $this->entityManager->getRepository(NetShipPositionHistory::class)
                                         ->findOneBy(['netShipdata' => $ship], ['timestamp' => 'DESC']);
        if ( $lastEntry )
        {
            $lastTime = $lastEntry->getTimestamp();
            $now = new \DateTimeImmutable();

            // Berechne die Differenz in Sekunden (3600 Sek = 1 Stunde)
            $diffInSeconds = $now->getTimestamp() - $lastTime->getTimestamp();
            //$this->logOrFlash('info', 'Time Diff.: ' . $diffInSeconds . ' sekunden');
            if ( $diffInSeconds < 1800 )
            {
                //$this->logOrFlash('info', 'Zeit zu kurz. return (' . $ship->getName() . ')' );
                // Weniger als eine Stunde vergangen -> Ignorieren
                return;
            }
        }

        $this->logger->debug( 'aisstream ' . $data['MetaData']['MMSI'] . ' Daten gespeichert');
        $this->processAisMessage($data, $ship);
    }

    private function processAisMessage( array $data, $ship ): void
    {
        if ( $data['MessageType'] === 'PositionReport' )
        {
            $pos  = $data['Message']['PositionReport'];

            $history = new NetShipPositionHistory();
            $history->setNetShipdata( $ship );
            $history->setLatitude( $pos['Latitude'] );
            $history->setLongitude( $pos['Longitude'] );
            $history->setSpeed( $pos['Sog'] ); // Speed over Ground
            $history->setCourse( $pos['Cog'] ); // Course over Ground
            $navStatusCode = (int)$pos['NavigationalStatus'];
            $navStatusEntity = $this->entityManager->getRepository(NetShipNavStatus::class)->findOneBy( [ 'status' => $navStatusCode ] );
            if ( $navStatusEntity )
            {
                $history->setNavstat( $navStatusEntity );
                $history->setZone( 'aisStream' );
            }
            $history->setTimestamp( new \DateTimeImmutable() );

            $this->entityManager->persist( $history );
        }
    }

    private function fetchShipMMSI(): array
    {
        $shipsToUpdate = $this->shipRepository->findAll();
        $relevantMmsis = [];

        foreach ( $shipsToUpdate as $ship )
        {
            $mmsi = $ship->getMmsi();

            if ( $mmsi && (int)$mmsi !== 0 )
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
}
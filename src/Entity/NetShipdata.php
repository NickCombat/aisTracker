<?php

namespace App\Entity;

use App\Repository\NetShipdataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\Criteria;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NetShipdataRepository::class)]
#[UniqueEntity(
    fields: ['MMSI'],
    message: 'Die MMSI Nummer ist immer eindeutig.'
)]
class NetShipdata
{
    public const STATUS_ACTIVE = 1;         // Im Dienst -> Wartung läuft
    public const STATUS_TEST = 5;           // Testphase -> Keine Wartung
    public const STATUS_OUTFITTING = 4;     // Ausrüstung -> Keine Wartung
    public const STATUS_MAINTENANCE = 10;   // Werft -> Wartung gestoppt/manuell
    public $total_count  = 0;
    public $defect_count = 0;
    public $check_count  = 0;
    public $last_action  = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Die IMO-Nummer darf nicht leer sein.')]
    private ?int $imo = null;

    #[ORM\Column(nullable: true)]
    #[Assert\NotBlank(message: 'Die MMSI-Nummer darf nicht leer sein.')]
    private ?int $MMSI = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $Rufzeichen = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $laenge = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $breite = null;

    #[ORM\ManyToOne(targetEntity: NetShipTyp::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?NetShipTyp $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pic = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private $isInLteRange = false;

    private int $imageCount = 0;

    private int $anlagenCount = 0;

    #[ORM\ManyToOne(inversedBy: 'netShipdatas')]
    private ?Flaggenstaaten $flag = null;

    /**
     * @var Collection<int, NetProjektAnlagen>
     */
    #[ORM\OneToMany(targetEntity: NetProjektAnlagen::class, mappedBy: 'projekt')]
    private Collection $netProjektAnlagens;

    /**
     * @var Collection<int, NetProjektGalerie>
     */
    #[ORM\OneToMany(targetEntity: NetProjektGalerie::class, mappedBy: 'projekt', cascade: ['remove'])]
    private Collection $netProjektGaleries;

    /**
     * @var Collection<int, NetShipdataPort>
     */
    #[ORM\OneToMany(targetEntity: NetShipdataPort::class, orphanRemoval: true, cascade: ['persist'], mappedBy: 'shipdata')]
    private Collection $netShipdataPorts;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $orderno = null;

    #[ORM\ManyToOne]
    private ?NetProjektStatus $status = null;

    private ?array $aisData = null;

    /**
     * @var Collection<int, NetShipPositionHistory>
     */
    #[ORM\OneToMany(mappedBy: 'netShipdata', targetEntity: NetShipPositionHistory::class, cascade: ['persist', 'remove'], orphanRemoval: true )]
    private Collection $netShipPositionHistories;

    #[ORM\Column]
    private ?bool $aisUpdate = null;

    /**
     * @var Collection<int, NetShipdataPortLog>
     */
    #[ORM\OneToMany(targetEntity: NetShipdataPortLog::class, mappedBy: 'shipdata', orphanRemoval: true)]
    private Collection $netShipdataPortLog;

    public function __construct()
    {
        $this->netProjektAnlagens = new ArrayCollection();
        $this->netProjektGaleries = new ArrayCollection();
        $this->netShipdataPorts = new ArrayCollection();
        $this->netShipPositionHistories = new ArrayCollection();
        $this->netShipdataPortLog = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getImo(): ?int
    {
        return $this->imo;
    }

    public function setImo( $imo): static
    {
        $this->imo = $imo;

        return $this;
    }

    public function getMMSI(): ?int
    {
        return $this->MMSI;
    }

    public function setMMSI(?int $MMSI): static
    {
        $this->MMSI = $MMSI;

        return $this;
    }

    public function getRufzeichen(): ?string
    {
        return $this->Rufzeichen;
    }

    public function setRufzeichen(?string $Rufzeichen): static
    {
        $this->Rufzeichen = $Rufzeichen;

        return $this;
    }

    public function getLaenge(): ?string
    {
        return $this->laenge;
    }

    public function setLaenge(?string $laenge): static
    {
        $this->laenge = $laenge;

        return $this;
    }

    public function getBreite(): ?string
    {
        return $this->breite;
    }

    public function setBreite(?string $breite): static
    {
        $this->breite = $breite;

        return $this;
    }

    public function getType(): ?NetShipTyp
    {
        return $this->type;
    }

    public function setType(?NetShipTyp $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getPic(): ?string
    {
        return $this->pic;
    }

    public function setPic(?string $pic): static
    {
        $this->pic = $pic;

        return $this;
    }

    public function getFlag(): ?Flaggenstaaten
    {
        return $this->flag;
    }

    public function setFlag(?Flaggenstaaten $flag): static
    {
        $this->flag = $flag;

        return $this;
    }

    public function isInLteRange(): ?bool
    {
        return $this->isInLteRange;
    }

    public function setIsInLteRange(bool $isInLteRange): self
    {
        $this->isInLteRange = $isInLteRange;
        return $this;
    }

    /**
     * @return Collection<int, NetProjektAnlagen>
     */
    public function getNetProjektAnlagens(): Collection
    {
        return $this->netProjektAnlagens;
    }

    public function addNetProjektAnlagen(NetProjektAnlagen $netProjektAnlagen): static
    {
        if (!$this->netProjektAnlagens->contains($netProjektAnlagen)) {
            $this->netProjektAnlagens->add($netProjektAnlagen);
            $netProjektAnlagen->setProjekt($this);
        }

        return $this;
    }

    public function removeNetProjektAnlagen(NetProjektAnlagen $netProjektAnlagen): static
    {
        if ($this->netProjektAnlagens->removeElement($netProjektAnlagen)) {
            // set the owning side to null (unless already changed)
            if ($netProjektAnlagen->getProjekt() === $this) {
                $netProjektAnlagen->setProjekt(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NetProjektGalerie>
     */
    public function getNetProjektGaleries(): Collection
    {
        return $this->netProjektGaleries;
    }

    public function addNetProjektGallery(NetProjektGalerie $netProjektGallery): static
    {
        if (!$this->netProjektGaleries->contains($netProjektGallery)) {
            $this->netProjektGaleries->add($netProjektGallery);
            $netProjektGallery->setProjekt($this);
        }

        return $this;
    }

    public function removeNetProjektGallery(NetProjektGalerie $netProjektGallery): static
    {
        if ($this->netProjektGaleries->removeElement($netProjektGallery)) {
            // set the owning side to null (unless already changed)
            if ($netProjektGallery->getProjekt() === $this) {
                $netProjektGallery->setProjekt(null);
            }
        }

        return $this;
    }

    public function getFutureNetShipdataPorts(): array
    {
        $now = new \DateTime();
        $now->setTime( 0, 0 ); // Heute 00:00 Uhr

        // Filtere Ports, die heute oder später starten oder deren Abfahrt weniger als 1 Tag her ist
        $futurePorts = $this->netShipdataPorts->filter( function ( $port ) use ( $now )
        {
            $departure = $port->getDeparture();
            if ( $departure === null )
            {
                return false;
            }
            $departurePlusOne = ( clone $departure )->modify( '+1 day' );

            return $departurePlusOne >= $now;
        } )->toArray();

        // Sortiere nach Abfahrt
        usort( $futurePorts, function ( $portA, $portB )
        {
            return $portA->getDeparture() <=> $portB->getDeparture();
        } );

        return $futurePorts;
    }

    /**
     * Baut eine Liste von aktuellen und zukünftigen Hafenbesuchen zusammen,
     * einschließlich derer, die innerhalb der letzten 24 Stunden endeten.
     *
     * @return array
     */
    public function getCurrentAndFuturePortVisits(): array
    {
        // Zeitpunkte für den Vergleich definieren
        $now = new \DateTimeImmutable();
        $twentyFourHoursAgo = $now->modify( '-24 hours' );

        $portVisits = [];

        // Hole ALLE Ankunfts-Ereignisse, sortiert nach Zeitstempel
        $allArrivalsCriteria = Criteria::create()
                                       ->where( Criteria::expr()
                                                        ->eq( 'eventType', 'ARRIVAL' ) )
                                       ->orderBy( [ 'eventTimestamp' => Criteria::ASC ] ); // Älteste zuerst

        $allArrivals = $this->netShipdataPortLog->matching( $allArrivalsCriteria );

        // Gehe jede Ankunft durch und finde die passende Abfahrt
        foreach ( $allArrivals as $arrivalEvent )
        {
            $departureEvent = null;

            // Kriterien für die Suche nach der passenden Abfahrt (genau wie vorher)
            $departureCriteria = Criteria::create()
                                         ->where( Criteria::expr()
                                                          ->eq( 'port', $arrivalEvent->getPort() ) )
                                         ->andWhere( Criteria::expr()
                                                             ->eq( 'eventType', 'DEPARTURE' ) )
                                         ->andWhere( Criteria::expr()
                                                             ->gt( 'eventTimestamp', $arrivalEvent->getEventTimestamp() ) )
                                         ->orderBy( [ 'eventTimestamp' => Criteria::ASC ] )
                                         ->setMaxResults( 1 );

            $departureEvent = $this->netShipdataPortLog->matching( $departureCriteria )
                                                       ->first();
            $departureTimestamp = $departureEvent ? $departureEvent->getEventTimestamp() : null;

            // Filter anwenden: Nur Besuche behalten, die noch andauern,
            //    zukünftig sind oder vor weniger als 24 Stunden endeten.
            $includeVisit = false;
            if ( $departureTimestamp === null )
            {
                if ( $arrivalEvent->getEventTimestamp() >= $twentyFourHoursAgo )
                { // Stellt sicher, dass wir nicht zu alte, unvollständige Einträge nehmen
                    $includeVisit = true;
                }
            }
            elseif ( $departureTimestamp >= $twentyFourHoursAgo )
            {
                $includeVisit = true;
            }

            if ( $includeVisit )
            {
                $portVisits[] = [ 'port'         => $arrivalEvent->getPort(),
                                  'arrival'      => $arrivalEvent->getEventTimestamp(),
                                  'departure'    => $departureTimestamp,
                                  'arrival_id'   => $arrivalEvent->getId(),
                                  'departure_id' => $departureEvent ? $departureEvent->getId() : null,
                ];
            }
        }

        usort( $portVisits, fn( $a, $b ) => $a['arrival'] <=> $b['arrival'] );

        return $portVisits;
    }

    /**
     * @return Collection<int, NetShipdataPort>
     */
    public function getNetShipdataPorts(): Collection
    {
        $now = new \DateTime(); // Aktuelles Datum
        // Filtere und sortiere die Ports, die in der Zukunft liegen
        return $this->netShipdataPorts->filter(function ($port) use ($now) {
            return $port->getArrival() > $now;  // Nur zukünftige Einträge
        });
    }

    public function addNetShipdataPort(NetShipdataPort $netShipdataPort): static
    {
        if (!$this->netShipdataPorts->contains($netShipdataPort)) {
            $this->netShipdataPorts->add($netShipdataPort);
            $netShipdataPort->setShipdata($this);
        }

        return $this;
    }

    public function removeNetShipdataPort(NetShipdataPort $netShipdataPort): static
    {
        if ($this->netShipdataPorts->removeElement($netShipdataPort)) {
            // set the owning side to null (unless already changed)
            if ($netShipdataPort->getShipdata() === $this) {
                $netShipdataPort->setShipdata(null);
            }
        }

        return $this;
    }

    /**
     * Holt den nächsten anzulaufenden Hafen.
     * Gibt den aktuellen Hafen zurück, wenn das Schiff dort liegt.
     *
     * @return NetShipdataPort|null
     */
    public function getNextPort(): ?NetShipdataPort
    {
        $now = new \DateTime();

        // 1. PRÜFUNG: Aktueller Hafen (Liegt das Schiff gerade in einem Hafen?)
        $currentPortCriteria = Criteria::create()
                                       ->where(Criteria::expr()->lte('arrival', $now))
                                       ->andWhere(Criteria::expr()->gt('departure', $now))
                                       ->setMaxResults(1);

        $currentPort = $this->netShipdataPorts->matching($currentPortCriteria)->first();

        // Wenn ein aktiver Hafen gefunden wurde, geben wir diesen sofort zurück
        if ($currentPort)
        {
            return $currentPort;
        }

        // --- 2. PRÜFUNG: Nächster geplanter Hafen ---
        // Wenn das Schiff NICHT im Hafen ist, suche den nächsten geplanten Hafen.
        $nextPortCriteria = Criteria::create()
            // suchen den Hafen, dessen Ankunftszeit in der Zukunft liegt
                                    ->where(Criteria::expr()->gt('arrival', $now))
                                    ->orderBy(['arrival' => Criteria::ASC])
                                    ->setMaxResults(1);

        $nextPort = $this->netShipdataPorts->matching($nextPortCriteria)->first();

        return $nextPort ?: null;
    }

    public function getCurrentOrNextPortLog(): ?NetShipdataPortLog
    {
        $now = new \DateTimeImmutable();

        // 1. Finde das letzte Ereignis, das bereits stattgefunden hat
        $lastEventCriteria = Criteria::create()
                                     ->where(Criteria::expr()->lte('eventTimestamp', $now))
                                     ->orderBy(['eventTimestamp' => Criteria::DESC]) // Das neueste zuerst
                                     ->setMaxResults(1);

        $lastEvent = $this->netShipdataPortLog->matching($lastEventCriteria)->first();

        // 2. Prüfen, ob das letzte Ereignis eine Ankunft war
        if ($lastEvent && $lastEvent->getEventType() === 'ARRIVAL') {
            // Ja, das Schiff ist aktuell in diesem Hafen.
            return $lastEvent;
        }

        // 3. Wenn nicht, suche die nächste geplante Ankunft in der Zukunft
        $nextArrivalCriteria = Criteria::create()
                                       ->where(Criteria::expr()->gt('eventTimestamp', $now))
                                       ->andWhere(Criteria::expr()->eq('eventType', 'ARRIVAL'))
                                       ->orderBy(['eventTimestamp' => Criteria::ASC]) // Das früheste zuerst
                                       ->setMaxResults(1);

        $nextArrival = $this->netShipdataPortLog->matching($nextArrivalCriteria)->first();

        return $nextArrival ?: null; // Gibt die nächste Ankunft oder null zurück
    }

    /**
     * Holt den letzten angelaufenen Hafen.
     *
     * @return NetShipdataPortLog|null
     */
    public function getLastPort(): ?NetShipdataPortLog
    {
        $now = new \DateTime();

        // Suche den Hafen, dessen Abfahrtszeit in der Vergangenheit liegt
        // und sortiere absteigend, um den jüngsten Abfahrtszeitpunkt zu finden.
        $lastPortCriteria = Criteria::create()
            // Wir suchen den Hafen, dessen Abfahrtszeit in der Vergangenheit liegt
                                    ->where(Criteria::expr()->lt('departure', $now))
            // Sortiere absteigend nach der Abfahrtszeit, um den zuletzt verlassenen Hafen zu finden
                                    ->orderBy(['departure' => Criteria::DESC])
                                    ->setMaxResults(1);

        $lastPort = $this->netShipdataPorts->matching($lastPortCriteria)->first();

        return $lastPort ?: null;
    }

    public function getOrderno(): ?int
    {
        return $this->orderno;
    }

    public function setOrderno(?int $orderno): static
    {
        $this->orderno = $orderno;

        return $this;
    }

    public function getImageCount(): int
    {
        if(0 === $this->imageCount)
        {
            $this->imageCount = (int) $this->getNetProjektGaleries()->count();
        }

        return $this->imageCount;
    }

    public function setImageCount(int $imageCount): void
    {
        $this->imageCount = $imageCount;
    }

    public function getAnlagenCount(): int
    {
        if( 0=== $this->anlagenCount )
        {
            $this->anlagenCount = (int) $this->getNetProjektAnlagens()->count();
        }

        return $this->anlagenCount;
    }

    public function setAnlagenCount(int $anlagenCount): void
    {
        $this->anlagenCount = $anlagenCount;
    }

    public function getStatus(): ?NetProjektStatus
    {
        return $this->status;
    }

    public function setStatus(?NetProjektStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAisData(): ?array
    {
        return $this->aisData;
    }

    public function setAisData( ?array $aisData ): void
    {
        $this->aisData = $aisData;
    }

    /**
     * @return Collection<int, NetShipPositionHistory>
     */
    public function getNetShipPositionHistories(): Collection
    {
        return $this->netShipPositionHistories;
    }

    public function addNetShipPositionHistories(NetShipPositionHistory $netShipPositionHistory): static
    {
        if (!$this->netShipPositionHistories->contains($netShipPositionHistory)) {
            $this->netShipPositionHistories->add($netShipPositionHistory);
            $netShipPositionHistory->setNetShipdata($this);
        }

        return $this;
    }

    public function removeNetShipPositionHistory(NetShipPositionHistory $netShipPositionHistory): static
    {
        if ($this->netShipPositionHistories->removeElement($netShipPositionHistory)) {
            if ($netShipPositionHistory->getNetShipdata() === $this) {
                $netShipPositionHistory->setNetShipdata(null);
            }
        }

        return $this;
    }

    /**
     * Gibt den letzten Positions-History-Eintrag zurück.
     * @return NetShipPositionHistory|null
     */
    public function getLatestPositionHistory(): ?NetShipPositionHistory
    {
        // Erstellt ein Kriterium für die Suche
        $criteria = Criteria::create()
                            ->orderBy(['timestamp' => Criteria::DESC])
                            ->setMaxResults(1);

        $latest = $this->netShipPositionHistories->matching($criteria)->first();

        return $latest instanceof NetShipPositionHistory ? $latest : null;
    }

    /**
     * Gibt die letzte Zeit der Positions-History-Eintrag zurück.
     *
     * @return \DateTimeImmutable|null
     * @throws \DateMalformedStringException
     */
    public function getLatestPositionCheck():?\DateTimeImmutable
    {
        //$dir = '/var/www/html/var/log/vesselfinder_raw/';            // develop
        $dir = '/var/www/html/netzwerktool/var/log/vesselfinder_raw/'; // live

        if(!$this->getLatestPositionHistory())
        {
            return null;
        }

        return $this->listDirectoryContents( $dir );
    }

    /**
     * @param string $dir
     * @return \DateTimeImmutable|null
     */
    private function listDirectoryContents( string $dir )
    {
        $maxEntries = 1000;
        $dateiArray = [];
        $entryCount = 0;

        // Prüfen, ob Verzeichnis existiert und lesbar ist
        if ( ! is_dir( $dir ) )
        {
            return null;
        }
        if ( ! is_readable( $dir ) )
        {
            return null;
        }

        if ( $handle = opendir( $dir ) )
        {
            while ( ( $file = readdir( $handle ) ) !== false )
            {
                if ( $entryCount >= $maxEntries )
                {
                    break;
                }
                if ( '.' === $file || '.' === substr( $file, 1 ) )
                {
                    continue;
                }

                $dateiName = htmlspecialchars( $file );
                $nameStrip    = explode( '.', $dateiName );
                $dateiKennung = explode( '_', $nameStrip[0] );

                if (!isset($dateiKennung[0], $dateiKennung[1]))
                {
                    continue;
                }

                $strtotime = strtotime( $dateiKennung[0] . ' ' . str_replace( '-', ':', $dateiKennung[1] ) );
                if ( ! isset( $dateiKennung[3] ) )
                {
                    continue;
                }
                if ( substr( $dateiKennung[3], 0, 3 ) === 'imo' )
                {
                    if ( (int)substr( $dateiKennung[3], 3 ) === $this->getImo() )
                    {
                        $dateiArray[ $strtotime ] = $strtotime;
                    }
                }
                elseif ( substr( $dateiKennung[3], 0, 4 ) === 'mmsi' )
                {
                    if ( (int)substr( $dateiKennung[3], 4 ) === $this->getMMSI() )
                    {
                        $dateiArray[ $strtotime ] = $strtotime;
                    }
                }
            }

            closedir( $handle );
        }

        krsort( $dateiArray );
        if ( empty( $dateiArray ) )
        {
            return null;
        }
        $timestamp = reset( $dateiArray );
        if ( $timestamp === false )
        {
            return null;
        }
        try
        {
            return new \DateTimeImmutable( '@' . $timestamp );
        }
        catch ( \Exception $e )
        {
            return null;
        }
    }

    /**
     * Baut eine Liste der letzten 10 vergangenen Hafenbesuche
     * aus den Log-Einträgen zusammen.
     *
     * @return array
     */
    public function getPastPortVisits(): array
    {
        $now = new \DateTimeImmutable();
        $portVisitsSegments = []; // Alle Segmente (wie im Original)

        // 1. Alle "Abfahrt -> Letzte Ankunft" Paare finden
        $start2024 = new \DateTimeImmutable('2024-01-01 00:00:00');
        $pastDeparturesCriteria = Criteria::create()
                                          ->where( Criteria::expr()
                                                           ->lt( 'eventTimestamp', $now ) )
                                          ->andWhere( Criteria::expr()
                                                              ->eq( 'eventType', 'DEPARTURE' ) )
                                          ->andWhere( Criteria::expr()
                                                              ->gte( 'eventTimestamp', $start2024 ) )
                                          ->orderBy( [ 'eventTimestamp' => Criteria::DESC ] );

        $pastDepartures = $this->netShipdataPortLog->matching( $pastDeparturesCriteria );

        foreach ( $pastDepartures as $departureEvent )
        {
            $arrivalCriteria = Criteria::create()
                                       ->where( Criteria::expr()
                                                        ->eq( 'port', $departureEvent->getPort() ) )
                                       ->andWhere( Criteria::expr()
                                                           ->eq( 'eventType', 'ARRIVAL' ) )
                                       ->andWhere( Criteria::expr()
                                                           ->lt( 'eventTimestamp', $departureEvent->getEventTimestamp() ) )
                                       ->orderBy( [ 'eventTimestamp' => Criteria::DESC ] )
                                       ->setMaxResults( 1 );

            $arrivalEvent = $this->netShipdataPortLog->matching( $arrivalCriteria )
                                                     ->first();

            $portVisitsSegments[] = [ 'port'      => $departureEvent->getPort(),
                                      'arrival'   => $arrivalEvent ? $arrivalEvent->getEventTimestamp() : null,
                                      'departure' => $departureEvent->getEventTimestamp(),
            ];
        }

        // --- 2. VERBESSERTE MERGE-LOGIK ---
        if ( empty( $portVisitsSegments ) )
        {
            return [];
        }

        $mergedVisits = [];
        // Toleranz für die Lücke zwischen Abfahrt und nächster Ankunft (z.B. 6 Stunden)
        $mergeToleranceSeconds = 6 * 3600;

        $currentVisit = array_shift( $portVisitsSegments );

        foreach ( $portVisitsSegments as $nextVisit )
        {
            if('Anch.' === substr($nextVisit['port']->getBezeichnung(),-5,5))
            {
                continue;
            }
            $isSamePort = $currentVisit['port'] === $nextVisit['port'];
            $isContiguous = false;

            // Prüfen, ob die Segmente zusammenhängen
            if ( $isSamePort && $currentVisit['arrival'] && $nextVisit['departure'] )
            {
                // Berechne die Lücke (in Sekunden)
                $gap = $currentVisit['arrival']->getTimestamp() - $nextVisit['departure']->getTimestamp();

                // Ist die Lücke positiv (arrival > departure) und klein (innerhalb der Toleranz)?
                if ( $gap >= 0 && $gap <= $mergeToleranceSeconds )
                {
                    $isContiguous = true;
                }
            }

            if ( $isSamePort && $isContiguous )
            {
                // Ja, zusammenhängend. Erweitere $currentVisit rückwärts.
                $currentVisit['arrival'] = $nextVisit['arrival'];
            }
            else
            {
                // Nein, neuer Besuch. Speichere den alten und starte neu.
                $mergedVisits[] = $this->calculateDuration( $currentVisit );
                $currentVisit = $nextVisit;
            }
        }

        // Den letzten verbleibenden Besuch hinzufügen
        $mergedVisits[] = $this->calculateDuration( $currentVisit );

        return $mergedVisits;
    }

    /**
     * Hilfsfunktion zur Berechnung der Dauer (um den Code sauber zu halten)
     * UND zur Validierung der Daten (verhindert negative Tage)
     */
    private function calculateDuration( array $visit ): array
    {
        $arrivalTimestamp = $visit['arrival'];
        $departureTimestamp = $visit['departure'];
        $durationDays = null;

        if ( $arrivalTimestamp && $departureTimestamp )
        {

            // SCHUTZ VOR FALSCHEN DATEN (z.B. Ankunft > Abfahrt)
            if ( $arrivalTimestamp > $departureTimestamp )
            {
                // Wenn die Daten invertiert sind, setzen wir die Dauer auf 0 oder null
                $durationDays = 0;
            }
            else
            {
                // Korrekte Berechnung der Kalendertage
                $dayStart = clone $arrivalTimestamp;
                $dayStart = $dayStart->setTime( 0, 0, 0 );

                $dayEnd = clone $departureTimestamp;
                $dayEnd = $dayEnd->setTime( 0, 0, 0 );

                $diff = $dayStart->diff( $dayEnd );
                $durationDays = $diff->days + 1;
            }
        }

        $visit['durationDays'] = $durationDays;

        return $visit;
    }

    public function isAisUpdate(): ?bool
    {
        return $this->aisUpdate;
    }

    public function setAisUpdate(bool $aisUpdate): static
    {
        $this->aisUpdate = $aisUpdate;

        return $this;
    }

    /**
     * @return Collection<int, NetShipdataPortLog>
     */
    public function getNetShipdataPortLog(): Collection
    {
        return $this->netShipdataPortLog;
    }

    public function addNetShipdataPortLog(NetShipdataPortLog $netShipdataPortLog): static
    {
        if (!$this->netShipdataPortLog->contains($netShipdataPortLog)) {
            $this->netShipdataPortLog->add($netShipdataPortLog);
            $netShipdataPortLog->setShipdata($this);
        }

        return $this;
    }

    public function removeEventTimestamp(NetShipdataPortLog $netShipdataPortLog): static
    {
        if ($this->netShipdataPortLog->removeElement($netShipdataPortLog)) {
            // set the owning side to null (unless already changed)
            if ($netShipdataPortLog->getShipdata() === $this) {
                $netShipdataPortLog->setShipdata(null);
            }
        }

        return $this;
    }

    // Helper-Methode: Ist das Schiff im Wartungs-Modus?
    public function isMaintenanceActive(): bool
    {
        if( $this->status === null)
        {
            return false;
        }

        return $this->status->getId() === self::STATUS_ACTIVE;
    }

}
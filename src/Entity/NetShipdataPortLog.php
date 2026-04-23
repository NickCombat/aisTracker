<?php

namespace App\Entity;

use App\Repository\NetShipdataPortLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NetShipdataPortLogRepository::class)]
#[UniqueEntity(
    fields: ['shipdata', 'eventTimestamp', 'eventType'],
    message: 'Dieser Log-Eintrag existiert bereits für dieses Schiff.'
)]
class NetShipdataPortLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'eventTimestamp')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NetShipdata $shipdata = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $eventTimestamp = null;

    #[ORM\Column(length: 50)]
    private ?string $eventType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?NetPort $port = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShipdata(): ?NetShipdata
    {
        return $this->shipdata;
    }

    public function setShipdata(?NetShipdata $shipdata): static
    {
        $this->shipdata = $shipdata;

        return $this;
    }

    public function getEventTimestamp(): ?\DateTimeImmutable
    {
        return $this->eventTimestamp;
    }

    public function setEventTimestamp(\DateTimeImmutable $eventTimestamp): static
    {
        $this->eventTimestamp = $eventTimestamp;

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getPort(): ?NetPort
    {
        return $this->port;
    }

    public function setPort(?NetPort $port): static
    {
        $this->port = $port;

        return $this;
    }
}

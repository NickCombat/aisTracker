<?php

namespace App\Entity;

use App\Repository\NetShipdataPortRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetShipdataPortRepository::class)]
class NetShipdataPort
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'netShipdataPorts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NetShipdata $shipdata = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $arrival = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $departure = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\NetPort')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NetPort $port = null;

    #[ORM\ManyToOne]
    private ?NetShipdataPortStatus $status = null;

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

    public function getArrival(): ?\DateTimeInterface
    {
        return $this->arrival;
    }

    public function setArrival(\DateTimeInterface $Arrival): static
    {
        $this->arrival = $Arrival;

        return $this;
    }

    public function getDeparture(): ?\DateTimeInterface
    {
        return $this->departure;
    }

    public function setDeparture(?\DateTimeInterface $Departure): static
    {
        $this->departure = $Departure;

        return $this;
    }

    // Getter und Setter für das neue port-Feld
    public function getPort(): ?NetPort
    {
        return $this->port;
    }

    public function setPort(?NetPort $port): static
    {
        $this->port = $port;

        return $this;
    }

    public function getStatus(): ?NetShipdataPortStatus
    {
        return $this->status;
    }

    public function setStatus(?NetShipdataPortStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
}

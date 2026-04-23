<?php
// src/Entity/NetShipPositionHistory.php

namespace App\Entity;

use App\Repository\NetShipPositionHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetShipPositionHistoryRepository::class)]
class NetShipPositionHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $timestamp = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $course = null;

    #[ORM\Column(nullable: true)]
    private ?float $speed = null;

    #[ORM\ManyToOne(targetEntity: NetShipNavStatus::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?NetShipNavStatus $navstat = null;

    #[ORM\Column(nullable: true)]
    private ?float $draught = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $destination = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $locode = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $eta = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $etaais = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $zone = null;

    #[ORM\ManyToOne(inversedBy: 'netShipPositionHistories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NetShipdata $netShipdata = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getCourse(): ?string
    {
        return $this->course;
    }

    public function setCourse(?string $course): self
    {
        $this->course = $course;

        return $this;
    }

    public function getSpeed(): ?float
    {
        return $this->speed;
    }

    public function setSpeed(?float $speed): self
    {
        $this->speed = $speed;

        return $this;
    }

    public function getNavstat(): ?NetShipNavStatus
    {
        return $this->navstat;
    }

    public function setNavstat(?NetShipNavStatus $navstat): self
    {
        $this->navstat = $navstat;

        return $this;
    }

    public function getDraught(): ?float
    {
        return $this->draught;
    }

    public function setDraught(?float $draught): self
    {
        $this->draught = $draught;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getLocode(): ?string
    {
        return $this->locode;
    }

    public function setLocode(?string $locode): self
    {
        $this->locode = $locode;

        return $this;
    }

    public function getEta(): ?\DateTimeImmutable
    {
        return $this->eta;
    }

    public function setEta(?\DateTimeImmutable $eta): self
    {
        $this->eta = $eta;

        return $this;
    }

    public function getEtaais(): ?string
    {
        return $this->etaais;
    }

    public function setEtaais(?string $etaais): self
    {
        $this->etaais = $etaais;

        return $this;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(?string $zone): self
    {
        $this->zone = $zone;

        return $this;
    }

    public function getNetShipdata(): ?NetShipdata
    {
        return $this->netShipdata;
    }

    public function setNetShipdata(?NetShipdata $NetShipdata): self
    {
        $this->netShipdata = $NetShipdata;

        return $this;
    }
}

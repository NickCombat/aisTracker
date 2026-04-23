<?php

namespace App\Entity;

use App\Repository\NetShipTypRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetShipTypRepository::class)]
class NetShipTyp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $bezeichnung = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Beschreibung = null;


    public function __toString(): string
    {
        return $this->bezeichnung;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBezeichnung(): ?string
    {
        return $this->bezeichnung;
    }

    public function setBezeichnung(string $bezeichnung): static
    {
        $this->bezeichnung = $bezeichnung;

        return $this;
    }

    public function getBeschreibung(): ?string
    {
        return $this->Beschreibung;
    }

    public function setBeschreibung(?string $Beschreibung): static
    {
        $this->Beschreibung = $Beschreibung;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\NetSeitenParameterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NetSeitenParameterRepository::class)]
#[UniqueEntity(
    fields: ['name'],
    message: 'Dieser Name wird bereits verwendet. Bitte wählen Sie einen anderen.'
)]
class NetSeitenParameter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $wert = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $beschreibung = null;

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

    public function getWert(): ?string
    {
        return $this->wert;
    }

    public function setWert(string $wert): static
    {
        $this->wert = $wert;

        return $this;
    }

    public function getBeschreibung(): ?string
    {
        return $this->beschreibung;
    }

    public function setBeschreibung(?string $beschreibung): static
    {
        $this->beschreibung = $beschreibung;

        return $this;
    }
}

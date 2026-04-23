<?php

namespace App\Entity;

use App\Repository\NetPortRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetPortRepository::class)]
class NetPort
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 6)]
    private ?string $kuerzel = null;

    #[ORM\Column(length: 100)]
    private ?string $bezeichnung = null;

    #[ORM\Column(length: 2)]
    private ?string $land = null;

    #[ORM\ManyToOne]
    private ?Flaggenstaaten $flag = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKuerzel(): ?string
    {
        return $this->kuerzel;
    }

    public function setKuerzel(string $kuerzel): static
    {
        $this->kuerzel = $kuerzel;

        return $this;
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

    public function getLand(): ?string
    {
        return $this->land;
    }

    public function setLand(string $land): static
    {
        $this->land = $land;

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
}

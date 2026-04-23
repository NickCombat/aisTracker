<?php

namespace App\Entity;

use App\Repository\FlaggenstaatenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlaggenstaatenRepository::class)]
class Flaggenstaaten
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $AmtlicheKurzform = null;

    #[ORM\Column(length: 150)]
    private ?string $AmtlicheVollform = null;

    #[ORM\Column(length: 2)]
    private ?string $kuerzel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $flagge = null;

    /**
     * @var Collection<int, NetShipdata>
     */
    #[ORM\OneToMany(targetEntity: NetShipdata::class, mappedBy: 'flag')]
    private Collection $netShipdatas;

    public function __construct()
    {
        $this->netShipdatas = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmtlicheKurzform(): ?string
    {
        return $this->AmtlicheKurzform;
    }

    public function setAmtlicheKurzform(string $AmtlicheKurzform): static
    {
        $this->AmtlicheKurzform = $AmtlicheKurzform;

        return $this;
    }

    public function getAmtlicheVollform(): ?string
    {
        return $this->AmtlicheVollform;
    }

    public function setAmtlicheVollform(string $AmtlicheVollform): static
    {
        $this->AmtlicheVollform = $AmtlicheVollform;

        return $this;
    }

    public function getKuerzel(): ?string
    {
        return $this->kuerzel;
    }

    public function setKuerzel( ?string $kuerzel ): void
    {
        $this->kuerzel = $kuerzel;
    }

    public function getFlagge(): ?string
    {
        return $this->flagge;
    }

    public function setFlagge(?string $flagge): static
    {
        $this->flagge = $flagge;

        return $this;
    }

    /**
     * @return Collection<int, NetShipdata>
     */
    public function getNetShipdatas(): Collection
    {
        return $this->netShipdatas;
    }

    public function addNetShipdata(NetShipdata $netShipdata): static
    {
        if (!$this->netShipdatas->contains($netShipdata)) {
            $this->netShipdatas->add($netShipdata);
            $netShipdata->setFlag($this);
        }

        return $this;
    }

    public function removeNetShipdata(NetShipdata $netShipdata): static
    {
        if ($this->netShipdatas->removeElement($netShipdata)) {
            // set the owning side to null (unless already changed)
            if ($netShipdata->getFlag() === $this) {
                $netShipdata->setFlag(null);
            }
        }

        return $this;
    }
}

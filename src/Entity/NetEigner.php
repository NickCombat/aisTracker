<?php

namespace App\Entity;

use App\Repository\NetEignerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetEignerRepository::class)]
class NetEigner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $bezeichnung = null;

    #[ORM\ManyToOne]
    private ?Flaggenstaaten $flag = null;

    #[ORM\Column(length: 100)]
    private ?string $sitz = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $leitung = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $webseite = null;

    #[ORM\Column(length: 12, nullable: true)]
    private ?string $gruendung = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $geschaeftsfeld = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wappen = null;

    #[ORM\Column(length: 50)]
    private ?string $kuerzel = null;

    /**
     * @var Collection<int, NetShipdata>
     */
    #[ORM\OneToMany(targetEntity: NetShipdata::class, mappedBy: 'eigner')]
    private Collection $netShipdatas;

    public function __construct()
    {
        $this->netShipdatas = new ArrayCollection();
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

    public function getFlag(): ?Flaggenstaaten
    {
        return $this->flag;
    }

    public function setFlag(?Flaggenstaaten $flag): static
    {
        $this->flag = $flag;

        return $this;
    }

    public function getSitz(): ?string
    {
        return $this->sitz;
    }

    public function setSitz(string $sitz): static
    {
        $this->sitz = $sitz;

        return $this;
    }

    public function getLeitung(): ?string
    {
        return $this->leitung;
    }

    public function setLeitung(?string $leitung): static
    {
        $this->leitung = $leitung;

        return $this;
    }

    public function getWebseite(): ?string
    {
        return $this->webseite;
    }

    public function setWebseite(?string $webseite): static
    {
        $this->webseite = $webseite;

        return $this;
    }

    public function getGruendung(): ?string
    {
        return $this->gruendung;
    }

    public function setGruendung(?string $gruendung): static
    {
        $this->gruendung = $gruendung;

        return $this;
    }

    public function getGeschaeftsfeld(): ?string
    {
        return $this->geschaeftsfeld;
    }

    public function setGeschaeftsfeld(?string $geschaeftsfeld): static
    {
        $this->geschaeftsfeld = $geschaeftsfeld;

        return $this;
    }

    public function getWappen(): ?string
    {
        return $this->wappen;
    }

    public function setWappen(?string $wappen): static
    {
        $this->wappen = $wappen;

        return $this;
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
            $netShipdata->setEigner($this);
        }

        return $this;
    }

    public function removeNetShipdata(NetShipdata $netShipdata): static
    {
        if ($this->netShipdatas->removeElement($netShipdata)) {
            // set the owning side to null (unless already changed)
            if ($netShipdata->getEigner() === $this) {
                $netShipdata->setEigner(null);
            }
        }

        return $this;
    }
}

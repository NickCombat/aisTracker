<?php

namespace App\Entity;

use App\Repository\NetProjektGalerieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetProjektGalerieRepository::class)]
class NetProjektGalerie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'netProjektGaleries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?NetShipdata $projekt = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $original_name = null;

    #[ORM\Column(length: 150)]
    private ?string $basename = null;

    #[ORM\Column(length: 150)]
    private ?string $filetype = null;

    #[ORM\Column(length: 10)]
    private ?string $filesize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bermerkung = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjekt(): ?NetShipdata
    {
        return $this->projekt;
    }

    public function setProjekt(?NetShipdata $projekt): static
    {
        $this->projekt = $projekt;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->original_name;
    }

    public function setOriginalName(string $original_name): static
    {
        $this->original_name = $original_name;

        return $this;
    }

    public function getBasename(): ?string
    {
        return $this->basename;
    }

    public function setBasename(string $basename): static
    {
        $this->basename = $basename;

        return $this;
    }

    public function getFiletype(): ?string
    {
        return $this->filetype;
    }

    public function setFiletype(string $filetype): static
    {
        $this->filetype = $filetype;

        return $this;
    }

    public function getFilesize(): ?string
    {
        return $this->filesize;
    }

    public function setFilesize(string $filesize): static
    {
        $this->filesize = $filesize;

        return $this;
    }

    public function getBermerkung(): ?string
    {
        return $this->bermerkung;
    }

    public function setBermerkung(?string $bermerkung): static
    {
        $this->bermerkung = $bermerkung;

        return $this;
    }
}
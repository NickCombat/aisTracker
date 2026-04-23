<?php

namespace App\Entity;

use App\Repository\NetProjektAnlagenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NetProjektAnlagenRepository::class)]
class NetProjektAnlagen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $filetype = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 10)]
    private ?string $filesize = null;

    #[ORM\ManyToOne(inversedBy: 'netProjektAnlagens')]
    private ?NetShipdata $projekt = null;

    #[ORM\Column(length: 150)]
    private ?string $originalName = null;

    #[ORM\Column(length: 150)]
    private ?string $basename = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $revision = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

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

    public function getProjekt(): ?NetShipdata
    {
        return $this->projekt;
    }

    public function setProjekt(?NetShipdata $projekt): static
    {
        $this->projekt = $projekt;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): static
    {
        $this->originalName = $originalName;

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

    public function getRevision(): ?string
    {
        return $this->revision;
    }

    public function setRevision(?string $revision): static
    {
        $this->revision = $revision;

        return $this;
    }
}

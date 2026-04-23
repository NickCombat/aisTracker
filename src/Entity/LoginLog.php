<?php

namespace App\Entity;

use App\Repository\LoginLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginLogRepository::class)]
class LoginLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'loginLogs')]
    private ?SecUser $user = null;

    #[ORM\Column]
    private ?\DateTime $loginTime = null;

    #[ORM\Column(length: 100)]
    private ?string $ipAdresse = null;

    #[ORM\Column(length: 150)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 10)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?SecUser
    {
        return $this->user;
    }

    public function setUser(?SecUser $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLoginTime(): ?\DateTime
    {
        return $this->loginTime;
    }

    public function setLoginTime(\DateTime $loginTime): static
    {
        $this->loginTime = $loginTime;

        return $this;
    }

    public function getIpAdresse(): ?string
    {
        return $this->ipAdresse;
    }

    public function setIpAdresse(string $ipAdresse): static
    {
        $this->ipAdresse = $ipAdresse;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}


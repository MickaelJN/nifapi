<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=LogRepository::class)
 */
class LogApi
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     */
    private $user;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $dataSend = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"log:read"})
     */
    private $path;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"log:read"})
     */
    private $method;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $codeHttp;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $returnValue = [];

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
    
    public function getDataSend(): ?array
    {
        return $this->dataSend;
    }

    public function setDataSend(?array $dataSend): self
    {
        $this->dataSend = $dataSend;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getCodeHttp(): ?int
    {
        return $this->codeHttp;
    }

    public function setCodeHttp(?int $codeHttp): self
    {
        $this->codeHttp = $codeHttp;

        return $this;
    }

    public function getReturnValue(): ?array
    {
        return $this->returnValue;
    }

    public function setReturnValue(?array $returnValue): self
    {
        $this->returnValue = $returnValue;

        return $this;
    }

}

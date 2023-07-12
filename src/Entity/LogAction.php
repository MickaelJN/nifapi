<?php

namespace App\Entity;

use App\Repository\LogActionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=LogActionRepository::class)
 */
class LogAction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"log:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"log:read"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"log:read"})
     */
    private $action;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"log:read"})
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @Groups({"log:read"})
     */
    private $author;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class)
     * @Groups({"log:read"})
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class)
     * @Groups({"log:read"})
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @Groups({"log:read"})
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Transfer::class)
     * @Groups({"log:read"})
     */
    private $transfer;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"log:read"})
     */
    private $data = [];

    /**
     * @ORM\ManyToOne(targetEntity=LogApi::class)
     * @Groups({"log:read"})
     */
    private $logApi;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;

        return $this;
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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

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

    public function getTransfer(): ?Transfer
    {
        return $this->transfer;
    }

    public function setTransfer(?Transfer $transfer): self
    {
        $this->transfer = $transfer;

        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getLogApi(): ?LogApi
    {
        return $this->logApi;
    }

    public function setLogApi(?LogApi $logApi): self
    {
        $this->logApi = $logApi;

        return $this;
    }
}

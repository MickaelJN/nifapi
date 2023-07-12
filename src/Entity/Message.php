<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=MessageRepository::class)
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"projectmessage:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=LogAction::class)
     * @Groups({"projectmessage:read"})
     */
    private $log;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectmessage:read"})
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"projectmessage:read"})
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @Groups({"projectmessage:read"})
     */
    private $user;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectmessage:read"})
     */
    private $content;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"projectmessage:read"})
     */
    private $data = [];

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="messages")
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLog(): ?LogAction
    {
        return $this->log;
    }

    public function setLog(?LogAction $log): self
    {
        $this->log = $log;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}

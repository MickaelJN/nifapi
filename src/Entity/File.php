<?php

namespace App\Entity;

use App\Repository\FileRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=FileRepository::class)
 */
class File
{
     /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"projectfull:read", "file:read", "user:read", "organizationfull:read", "transferfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"projectfull:read", "file:read", "user:read", "organizationfull:read"})
     */
    private $name;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"projectfull:read", "file:read", "user:read", "organizationfull:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"projectfull:read", "file:read", "user:read", "organizationfull:read"})
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"projectfull:read", "file:read", "user:read"})
     */
    private $typemine;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"projectfull:read", "file:read", "user:read"})
     */
    private $extension;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"projectfull:read", "file:read", "user:read"})
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="files")
     */
    private $project;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "file:read", "user:read", "organizationfull:read"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $slug;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class, inversedBy="oldFiles")
     */
    private $organization;



    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getTypemine(): ?string
    {
        return $this->typemine;
    }

    public function setTypemine(?string $typemine): self
    {
        $this->typemine = $typemine;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

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

}

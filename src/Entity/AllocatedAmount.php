<?php

namespace App\Entity;

use App\Repository\AllocatedAmountRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=AllocatedAmountRepository::class)
 */
class AllocatedAmount {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read","projectwp:read"})
     */
    private $amount;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $dateAllocated;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $reserve;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="extensions")
     * @ORM\JoinColumn(nullable=true)
     */
    private $project;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read","projectwp:read"})
     */
    private $dateSign;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $data = [];

    /**
     * @ORM\Column(type="text", nullable=true)
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $note;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $dateCheck;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     */
    private $file;

    public function getId(): ?int {
        return $this->id;
    }

    public function getAmount(): ?string {
        return $this->amount;
    }

    public function setAmount(?string $amount): self {
        $this->amount = $amount;

        return $this;
    }

    public function getDateAllocated(): ?\DateTimeInterface {
        return $this->dateAllocated;
    }

    public function setDateAllocated(?\DateTimeInterface $dateAllocated): self {
        $this->dateAllocated = $dateAllocated;

        return $this;
    }

    public function getReserve(): ?string {
        return $this->reserve;
    }

    public function setReserve(?string $reserve): self {
        $this->reserve = $reserve;

        return $this;
    }

    public function getProject(): ?Project {
        return $this->project;
    }

    public function setProject(?Project $project): self {
        $this->project = $project;

        return $this;
    }

    public function getDateSign(): ?\DateTimeInterface {
        return $this->dateSign;
    }

    public function setDateSign(?\DateTimeInterface $dateSign): self {
        $this->dateSign = $dateSign;

        return $this;
    }

    public function getData(): ?array {
        return $this->data;
    }

    public function setData(?array $data): self {
        $this->data = $data;

        return $this;
    }

    public function getNote(): ?string {
        return $this->note;
    }

    public function setNote(?string $note): self {
        $this->note = $note;

        return $this;
    }

    public function getDateCheck(): ?\DateTimeInterface {
        return $this->dateCheck;
    }

    public function setDateCheck(?\DateTimeInterface $dateCheck): self {
        $this->dateCheck = $dateCheck;

        return $this;
    }

    public function getFile(): ?File {
        return $this->file;
    }

    public function setFile(?File $file): self {
        $this->file = $file;

        return $this;
    }

}

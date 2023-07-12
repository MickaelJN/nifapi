<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=ReportRepository::class)
 */
class Report
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"projectfull:read", "payment:read", "report:read", "transferfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $retard;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $newEndDate;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $problems;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $changeObjectif;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $changeObjectifDescription;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $totalExpense;

    /**
     * @ORM\ManyToOne(targetEntity=File::class)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $pdf;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $refusDescription;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "reportfull:read"})
     */
    private $comment;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isFinal;
    
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isRetard(): ?bool
    {
        return $this->retard;
    }

    public function setRetard(?bool $retard): self
    {
        $this->retard = $retard;

        return $this;
    }

    public function getNewEndDate(): ?\DateTimeInterface
    {
        return $this->newEndDate;
    }

    public function setNewEndDate(?\DateTimeInterface $newEndDate): self
    {
        $this->newEndDate = $newEndDate;

        return $this;
    }

    public function getProblems(): ?string
    {
        return $this->problems;
    }

    public function setProblems(?string $problems): self
    {
        $this->problems = $problems;

        return $this;
    }

    public function isChangeObjectif(): ?bool
    {
        return $this->changeObjectif;
    }

    public function setChangeObjectif(?bool $changeObjectif): self
    {
        $this->changeObjectif = $changeObjectif;

        return $this;
    }

    public function getChangeObjectifDescription(): ?string
    {
        return $this->changeObjectifDescription;
    }

    public function setChangeObjectifDescription(?string $changeObjectifDescription): self
    {
        $this->changeObjectifDescription = $changeObjectifDescription;

        return $this;
    }

    public function getTotalExpense(): ?string
    {
        return $this->totalExpense;
    }

    public function setTotalExpense(?string $totalExpense): self
    {
        $this->totalExpense = $totalExpense;

        return $this;
    }

    public function getPdf(): ?File
    {
        return $this->pdf;
    }

    public function setPdf(?File $pdf): self
    {
        $this->pdf = $pdf;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRefusDescription(): ?string
    {
        return $this->refusDescription;
    }

    public function setRefusDescription(?string $refusDescription): self
    {
        $this->refusDescription = $refusDescription;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getIsFinal(): ?bool
    {
        return $this->isFinal;
    }

    public function setIsFinal(?bool $isFinal): self
    {
        $this->isFinal = $isFinal;

        return $this;
    }
    
    public function toArray()
    {
        return get_object_vars($this);
    }
}

<?php

namespace App\Entity;

use App\Repository\InvoiceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=InvoiceRepository::class)
 */
class Invoice
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $supplier;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $amountToPay;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $initialAmount;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $amountValid;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $percentage;

    /**
     * @ORM\Column(type="string", length=512, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $cause;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $dateAdd;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $dateDecision;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="invoices")
     */
    private $project;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $proof;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $reserve;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $reservePercentage;

    /**
     * @ORM\ManyToOne(targetEntity=Payment::class, inversedBy="invoices",cascade={"persist"})
     */
    private $payment;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $causeAuto;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(string $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getAmountToPay(): ?string
    {
        return $this->amountToPay;
    }

    public function setAmountToPay(?string $amountToPay): self
    {
        $this->amountToPay = $amountToPay;

        return $this;
    }

    public function getInitialAmount(): ?string
    {
        return $this->initialAmount;
    }

    public function setInitialAmount(?string $initialAmount): self
    {
        $this->initialAmount = $initialAmount;

        return $this;
    }

    public function getAmountValid(): ?string
    {
        return $this->amountValid;
    }

    public function setAmountValid(?string $amountValid): self
    {
        $this->amountValid = $amountValid;

        return $this;
    }

    public function getPercentage(): ?string
    {
        return $this->percentage;
    }

    public function setPercentage(?string $percentage): self
    {
        $this->percentage = $percentage;

        return $this;
    }

    public function getCause(): ?string
    {
        return $this->cause;
    }

    public function setCause(?string $cause): self
    {
        $this->cause = $cause;

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

    public function getDateAdd(): ?\DateTimeInterface
    {
        return $this->dateAdd;
    }

    public function setDateAdd(?\DateTimeInterface $dateAdd): self
    {
        $this->dateAdd = $dateAdd;

        return $this;
    }

    public function getDateDecision(): ?\DateTimeInterface
    {
        return $this->dateDecision;
    }

    public function setDateDecision(?\DateTimeInterface $dateDecision): self
    {
        $this->dateDecision = $dateDecision;

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

    public function getProof(): ?File
    {
        return $this->proof;
    }

    public function setProof(?File $proof): self
    {
        $this->proof = $proof;

        return $this;
    }

    public function getReserve(): ?string
    {
        return $this->reserve;
    }

    public function setReserve(?string $reserve): self
    {
        $this->reserve = $reserve;

        return $this;
    }

    public function getReservePercentage(): ?string
    {
        return $this->reservePercentage;
    }

    public function setReservePercentage(?string $reservePercentage): self
    {
        $this->reservePercentage = $reservePercentage;

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function getCauseAuto(): ?string
    {
        return $this->causeAuto;
    }

    public function setCauseAuto(?string $causeAuto): self
    {
        $this->causeAuto = $causeAuto;

        return $this;
    }
}

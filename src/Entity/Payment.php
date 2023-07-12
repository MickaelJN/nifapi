<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=PaymentRepository::class)
 */
class Payment
{
   /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"projectfull:read", "payment:read", "transferfull:read"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="payments")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"transferfull:read", "paymenttransfer:read"})
     */
    private $project;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read"})
     * @Groups({"projectfull:read", "payment:read", "transferfull:read"})
     */
    private $datePayment;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read"})
     * @Groups({"projectfull:read", "payment:read", "transferfull:read"})
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read"})
     * @Groups({"projectfull:read", "payment:read", "transferfull:read"})
     */
    private $receiptValidDate;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"projectfull:read"})
     * @Groups({"projectfull:read", "payment:read", "transferfull:read"})
     */
    private $isReserve;

    /**
     * @ORM\ManyToOne(targetEntity=Transfer::class, inversedBy="payments",cascade={"persist"})
     * @Groups({"projectfull:read", "payment:read"})
     */
    private $transfer;

    /**
     * @ORM\OneToMany(targetEntity=Invoice::class, mappedBy="payment")
     * @Groups({"transferfull:read"})
     */
    private $invoices;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $receiptData = [];

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $receipt;

    /**
     * @ORM\OneToOne(targetEntity=Report::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read", "paymentfull:read", "transferfull:read"})
     */
    private $report;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $oldId;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $ribData = [];
    

    public function __construct()
    {
        $this->invoices = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
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

    public function getDatePayment(): ?\DateTimeInterface
    {
        return $this->datePayment;
    }

    public function setDatePayment(?\DateTimeInterface $datePayment): self
    {
        $this->datePayment = $datePayment;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReceiptValidDate(): ?\DateTimeInterface
    {
        return $this->receiptValidDate;
    }

    public function setReceiptValidDate(?\DateTimeInterface $receiptValidDate): self
    {
        $this->receiptValidDate = $receiptValidDate;

        return $this;
    }

    public function isReserve(): ?bool
    {
        return $this->isReserve;
    }

    public function setReserve(?bool $isReserve): self
    {
        $this->isReserve = $isReserve;

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

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): self
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setPayment($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getPayment() === $this) {
                $invoice->setPayment(null);
            }
        }

        return $this;
    }

    public function getReceiptData(): ?array
    {
        return $this->receiptData;
    }

    public function setReceiptData(?array $receiptData): self
    {
        $this->receiptData = $receiptData;

        return $this;
    }

    public function getReceipt(): ?File
    {
        return $this->receipt;
    }

    public function setReceipt(?File $receipt): self
    {
        $this->receipt = $receipt;

        return $this;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): self
    {
        $this->report = $report;

        return $this;
    }

    public function getOldId(): ?int
    {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): self
    {
        $this->oldId = $oldId;

        return $this;
    }

    public function getRibData(): ?array
    {
        return $this->ribData;
    }

    public function setRibData(?array $ribData): self
    {
        $this->ribData = $ribData;

        return $this;
    }
}

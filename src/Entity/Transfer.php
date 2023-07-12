<?php

namespace App\Entity;

use App\Repository\TransferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TransferRepository::class)
 */
class Transfer {

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"projectfull:read", "payment:read", "transferfull:read", "transfer:read", "log:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"projectfull:read", "payment:read", "transferfull:read", "transfer:read", "log:read"})
     */
    private $year;

    /**
     * @ORM\Column(type="string", length=128, nullable=true )
     * @Groups({"projectfull:read", "payment:read", "transferfull:read", "transfer:read", "log:read"})
     */
    private $month;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read", "payment:read", "transferfull:read", "transfer:read", "log:read"})
     */
    private $dateExecution;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @Groups({"projectfull:read", "payment:read", "transferfull:read", "transfer:read"})
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="transfer")
     * @Groups({"transferfull:read"})
     */
    private $payments;

    /**
     * @ORM\Column(type="decimal", precision=12, scale=2, nullable=true)
     * @Groups({"projectfull:read", "payment:read", "transferfull:read", "transfer:read"})
     */
    private $amount;

    public function __construct() {
        $this->payments = new ArrayCollection();
    }

    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getYear(): ?int {
        return $this->year;
    }

    public function setYear(?int $year): self {
        $this->year = $year;

        return $this;
    }

    public function getMonth(): ?string {
        return $this->month;
    }

    public function setMonth(?string $month): self {
        $this->month = $month;

        return $this;
    }

    public function getDateExecution(): ?\DateTimeInterface {
        return $this->dateExecution;
    }

    public function setDateExecution(?\DateTimeInterface $dateExecution): self {
        $this->dateExecution = $dateExecution;

        return $this;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function setStatus(?string $status): self {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection {
        return $this->payments;
    }

    public function addPayment(Payment $payment): self {
        if (!$this->payments->contains($payment)) {
            $this->payments[] = $payment;
            $payment->setTransfer($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getTransfer() === $this) {
                $payment->setTransfer(null);
            }
        }

        return $this;
    }

    public function getAmount(): ?string {
        return $this->amount;
    }

    public function setAmount(?string $amount): self {
        $this->amount = $amount;

        return $this;
    }

    public function calculTotalAmount() {
        if ($this->getStatus() != "new") {
            $total = 0;
            foreach ($this->getPayments() as $payment) {
                $total += $payment->getAmount();
            }
            return $total;
        }
        return null;
    }

}

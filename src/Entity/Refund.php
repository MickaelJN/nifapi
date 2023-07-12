<?php

namespace App\Entity;

use App\Repository\RefundRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=RefundRepository::class)
 */
class Refund
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $amount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $dateAsk;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $dateRefund;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $initialReserve;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $amountToPay;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $justification;

    /**
     * @ORM\OneToOne(targetEntity=Project::class, mappedBy="refund", cascade={"persist", "remove"})
     */
    private $project;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read", "paymentfull:read"})
     */
    private $dateSend;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateAsk(): ?\DateTimeInterface
    {
        return $this->dateAsk;
    }

    public function setDateAsk(?\DateTimeInterface $dateAsk): self
    {
        $this->dateAsk = $dateAsk;

        return $this;
    }

    public function getDateRefund(): ?\DateTimeInterface
    {
        return $this->dateRefund;
    }

    public function setDateRefund(?\DateTimeInterface $dateRefund): self
    {
        $this->dateRefund = $dateRefund;

        return $this;
    }

    public function getInitialReserve(): ?string
    {
        return $this->initialReserve;
    }

    public function setInitialReserve(?string $initialReserve): self
    {
        $this->initialReserve = $initialReserve;

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

    public function getJustification(): ?string
    {
        return $this->justification;
    }

    public function setJustification(?string $justification): self
    {
        $this->justification = $justification;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        // unset the owning side of the relation if necessary
        if ($project === null && $this->project !== null) {
            $this->project->setRefund(null);
        }

        // set the owning side of the relation if necessary
        if ($project !== null && $project->getRefund() !== $this) {
            $project->setRefund($this);
        }

        $this->project = $project;

        return $this;
    }

    public function getDateSend(): ?\DateTimeInterface
    {
        return $this->dateSend;
    }

    public function setDateSend(?\DateTimeInterface $dateSend): self
    {
        $this->dateSend = $dateSend;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=SubscriptionRepository::class)
 */
class Subscription
{
    /**
     *@ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"subscription:read", "subscriptionfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="json")
     * @Groups({"subscriptionfull:read", "subscriptionfull:read"})
     */
    private $data = [];

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @Groups({"subscription:read", "subscriptionfull:read"})
     */
    private $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"subscription:read", "subscriptionfull:read"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"subscription:read", "subscriptionfull:read"})
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"subscription:read", "subscriptionfull:read"})
     */
    private $comment;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"subscription:read", "subscriptionfull:read"})
     */
    private $alreadyRead;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

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

    public function getAlreadyRead(): ?bool
    {
        return $this->alreadyRead;
    }

    public function setAlreadyRead(bool $alreadyRead): self
    {
        $this->alreadyRead = $alreadyRead;

        return $this;
    }
}

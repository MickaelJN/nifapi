<?php

namespace App\Entity;

use App\Repository\PhaseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PhaseRepository::class)
 */
class Phase {

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"projectfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $cause;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $objectif;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $objectif2;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $resources;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $beneficiary;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $cost;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $funding;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $solicitation;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $comment;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $duration;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $commentNif;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $location;

    public function getId(): ?int {
        return $this->id;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(string $description): self {
        $this->description = $description;

        return $this;
    }

    public function getCause(): ?string {
        return $this->cause;
    }

    public function setCause(string $cause): self {
        $this->cause = $cause;

        return $this;
    }

    public function getObjectif(): ?string {
        return $this->objectif;
    }

    public function setObjectif(string $objectif): self {
        $this->objectif = $objectif;

        return $this;
    }

    public function getObjectif2(): ?string {
        return $this->objectif2;
    }

    public function setObjectif2(string $objectif2): self {
        $this->objectif2 = $objectif2;

        return $this;
    }

    public function getResources(): ?string {
        return $this->resources;
    }

    public function setResources(string $resources): self {
        $this->resources = $resources;

        return $this;
    }

    public function getBeneficiary(): ?string {
        return $this->beneficiary;
    }

    public function setBeneficiary(string $beneficiary): self {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    public function getCost(): ?string {
        return $this->cost;
    }

    public function setCost(string $cost): self {
        $this->cost = $cost;

        return $this;
    }

    public function getFunding(): ?string {
        return $this->funding;
    }

    public function setFunding(string $funding): self {
        $this->funding = $funding;

        return $this;
    }

    public function getSolicitation(): ?string {
        return $this->solicitation;
    }

    public function setSolicitation(string $solicitation): self {
        $this->solicitation = $solicitation;

        return $this;
    }

    public function getComment(): ?string {
        return $this->comment;
    }

    public function setComment(string $comment): self {
        $this->comment = $comment;

        return $this;
    }

    public function getDuration(): ?string {
        return $this->duration;
    }

    public function setDuration(?string $duration): self {
        $this->duration = $duration;

        return $this;
    }

    public function getCommentNif(): ?string {
        return $this->commentNif;
    }

    public function setCommentNif(?string $commentNif): self {
        $this->commentNif = $commentNif;

        return $this;
    }

    public function getLocation(): ?string {
        return $this->location;
    }

    public function setLocation(?string $location): self {
        $this->location = $location;

        return $this;
    }

}

<?php

namespace App\Entity;

use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=CountryRepository::class)
 */
class Country
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read", "country:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read", "projectwp:read", "country:read"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read", "country:read"})
     */
    private $isocode2;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"organizationfull:read", "transferfull:read", "country:read"})
     */
    private $region;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"country:read"})
     */
    private $isSepa;

    public function getId(): ?int
    {
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

    public function getIsocode2(): ?string
    {
        return $this->isocode2;
    }

    public function setIsocode2(?string $isocode2): self
    {
        $this->isocode2 = $isocode2;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getIsSepa(): ?bool
    {
        return $this->isSepa;
    }

    public function setIsSepa(?bool $isSepa): self
    {
        $this->isSepa = $isSepa;

        return $this;
    }
}

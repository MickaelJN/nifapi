<?php

namespace App\Entity;

use App\Repository\RibRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=RibRepository::class)
 */
class Rib
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @ORM\Column(type="integer")
     * @Groups({"organization:read", "organizationfull:read","projectfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $iban;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $bic;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $bank;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $address;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $isSepa;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $file;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"organization:read", "organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $isValid;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $country;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $newRib = [];

   public function getId(): ?Uuid {
        return $this->id;
    }

   public function getIban(): ?string
   {
       return str_replace("-", "", str_replace(" ", "", trim($this->iban)));
   }

   public function setIban(?string $iban): self
   {
       $this->iban = str_replace("-", "", str_replace(" ", "", trim($iban)));

       return $this;
   }

   public function getBic(): ?string
   {
       return $this->bic;
   }

   public function setBic(?string $bic): self
   {
       $this->bic = $bic;

       return $this;
   }

   public function getBank(): ?string
   {
       return $this->bank;
   }

   public function setBank(?string $bank): self
   {
       $this->bank = $bank;

       return $this;
   }

   public function getAddress(): ?string
   {
       return $this->address;
   }

   public function setAddress(?string $address): self
   {
       $this->address = $address;

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

   public function getFile(): ?File
   {
       return $this->file;
   }

   public function setFile(?File $file): self
   {
       $this->file = $file;

       return $this;
   }

   public function getIsValid(): ?bool
   {
       return $this->isValid;
   }

   public function setIsValid(?bool $isValid): self
   {
       $this->isValid = $isValid;

       return $this;
   }

   public function getCountry(): ?Country
   {
       return $this->country;
   }

   public function setCountry(?Country $country): self
   {
       $this->country = $country;

       return $this;
   }

   public function getNewRib(): ?array
   {
       return $this->newRib;
   }

   public function setNewRib(?array $newRib): self
   {
       $this->newRib = $newRib;

       return $this;
   }
}

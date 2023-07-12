<?php

namespace App\Entity;

use App\Repository\OrganizationLocalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass=OrganizationLocalRepository::class)
 */
class OrganizationLocal {

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"organizationLocal:read", "organizationLocalfull:read", "projectfull:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     * @Groups({"user:read", "organizationLocal:read", "organizationLocalfull:read", "projectfull:read"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read", "projectfull:read"})
     */
    private $legalStatus;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"organizationLocalfull:read","projectfull:read"})
     */
    private $acronym;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"organizationLocalfull:read","projectfull:read"})
     */
    private $identificationNumber;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"organizationLocalfull:read","projectfull:read"})
     */
    private $website;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"organizationLocalfull:read","projectfull:read"})
     */
    private $dateOfEstablishment;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $dateOfPublication;
    
    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $headquarterAddress;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $headquarterZipcode;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $headquarterCity;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $headquarterCountry;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"organizationLocalfull:read", "projectfull:read"})
     */
    private $headquarterPostalbox;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="locals")
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;
    

    public function __construct() {
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;

        return $this;
    }

    public function getLegalStatus(): ?string {
        return $this->legalStatus;
    }

    public function setLegalStatus(?string $legalStatus): self {
        $this->legalStatus = $legalStatus;

        return $this;
    }

    public function getAcronym(): ?string {
        return $this->acronym;
    }

    public function setAcronym(?string $acronym): self {
        $this->acronym = $acronym;

        return $this;
    }

    public function getIdentificationNumber(): ?string {
        return $this->identificationNumber;
    }

    public function setIdentificationNumber(?string $identificationNumber): self {
        $this->identificationNumber = $identificationNumber;

        return $this;
    }

    public function getWebsite(): ?string {
        return $this->website;
    }

    public function setWebsite(?string $website): self {
        $this->website = $website;

        return $this;
    }

    public function getDateOfEstablishment(): ?\DateTimeInterface {
        return $this->dateOfEstablishment;
    }

    public function setDateOfEstablishment(?\DateTimeInterface $dateOfEstablishment): self {
        $this->dateOfEstablishment = $dateOfEstablishment;

        return $this;
    }

    public function getDateOfPublication(): ?\DateTimeInterface {
        return $this->dateOfPublication;
    }

    public function setDateOfPublication(?\DateTimeInterface $dateOfPublication): self {
        $this->dateOfPublication = $dateOfPublication;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }


    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getHeadquarterAddress(): ?string
    {
        return $this->headquarterAddress;
    }

    public function setHeadquarterAddress(?string $headquarterAddress): self
    {
        $this->headquarterAddress = $headquarterAddress;

        return $this;
    }

    public function getHeadquarterZipcode(): ?string
    {
        return $this->headquarterZipcode;
    }

    public function setHeadquarterZipcode(?string $headquarterZipcode): self
    {
        $this->headquarterZipcode = $headquarterZipcode;

        return $this;
    }

    public function getHeadquarterCity(): ?string
    {
        return $this->headquarterCity;
    }

    public function setHeadquarterCity(?string $headquarterCity): self
    {
        $this->headquarterCity = $headquarterCity;

        return $this;
    }

    public function getHeadquarterCountry(): ?Country
    {
        return $this->headquarterCountry;
    }

    public function setHeadquarterCountry(?Country $headquarterCountry): self
    {
        $this->headquarterCountry = $headquarterCountry;

        return $this;
    }

    public function getHeadquarterPostalbox(): ?string
    {
        return $this->headquarterPostalbox;
    }

    public function setHeadquarterPostalbox(?string $headquarterPostalbox): self
    {
        $this->headquarterPostalbox = $headquarterPostalbox;

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

}

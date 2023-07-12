<?php

namespace App\Entity;

use App\Repository\OrganizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * @ORM\Entity(repositoryClass=OrganizationRepository::class)
 */
class Organization {

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"project:read","user:read", "organization:read", "organizationfull:read", "projectfull:read", "transferfull:read", "log:read", "organizationshort:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     * @Groups({"project:read", "user:read", "organization:read", "organizationfull:read", "projectfull:read", "transferfull:read", "log:read", "organizationshort:read", "projectwp:read"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $legalStatus;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $acronym;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $identificationNumber;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read", "projectwp:read"})
     */
    private $website;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $dateOfEstablishment;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $dateOfPublication;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="organization")
     * @Groups({"user:read", "organizationfull:read"})
     */
    private $projects;

    /**
     * @ORM\OneToOne(targetEntity=Rib::class, cascade={"persist", "remove"})
     * @Groups({"organization:read", "organizationfull:read", "projectfull:read", "transferfull:read"})
     */
    private $rib;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $facebook;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $instagram;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $headquarterAddress;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $headquarterZipcode;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $headquarterCity;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $headquarterCountry;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $headquarterPostalbox;
    
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $officeAddress;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $officeZipcode;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $officeCity;

    /**
     * @ORM\ManyToOne(targetEntity=Country::class)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $officeCountry;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $officePostalbox;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"organizationfull:read", "projectfull:read", "organization:read"})
     */
    private $annexeStatus;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"organizationfull:read", "projectfull:read", "organization:read"})
     */
    private $annexeReport;

    /**
     * @ORM\OneToOne(targetEntity=File::class, cascade={"persist", "remove"})
     * @Groups({"organizationfull:read", "projectfull:read", "organization:read"})
     */
    private $annexeAccount;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="organization")
     * @Groups({"organizationfull:read", "projectfull:read"})
     */
    private $contacts;

    /**
     * @ORM\OneToOne(targetEntity=User::class, cascade={"persist", "remove"})
     * @Groups({"organizationfull:read", "projectfull:read", "user:read", "organization:read"})
     */
    private $representative;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $oldId;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isConfirm;

    /**
     * @ORM\OneToMany(targetEntity=File::class, mappedBy="organization")
     * @Groups({"organizationfull:read"})
     */
    private $oldFiles;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"organizationfull:read", "projectfull:read", "user:read", "organization:read"})
     */
    private $isActive;

    public function __construct() {
        $this->active = true;
        $this->projects = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->oldFiles = new ArrayCollection();
    }

    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = trim(mb_strtoupper($name));;

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

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection {
        return $this->projects;
    }

    public function addProject(Project $project): self {
        if (!$this->projects->contains($project)) {
            $this->projects[] = $project;
            $project->setOrganization($this);
        }

        return $this;
    }

    public function removeProject(Project $project): self {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getOrganization() === $this) {
                $project->setOrganization(null);
            }
        }

        return $this;
    }

    public function getRib(): ?Rib {
        return $this->rib;
    }

    public function setRib(?Rib $rib): self {
        $this->rib = $rib;

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

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

    public function getOfficeAddress(): ?string
    {
        return $this->officeAddress;
    }

    public function setOfficeAddress(?string $officeAddress): self
    {
        $this->officeAddress = $officeAddress;

        return $this;
    }

    public function getOfficeZipcode(): ?string
    {
        return $this->officeZipcode;
    }

    public function setOfficeZipcode(?string $officeZipcode): self
    {
        $this->officeZipcode = $officeZipcode;

        return $this;
    }

    public function getOfficeCity(): ?string
    {
        return $this->officeCity;
    }

    public function setOfficeCity(?string $officeCity): self
    {
        $this->officeCity = $officeCity;

        return $this;
    }

    public function getOfficeCountry(): ?Country
    {
        return $this->officeCountry;
    }

    public function setOfficeCountry(?Country $officeCountry): self
    {
        $this->officeCountry = $officeCountry;

        return $this;
    }

    public function getOfficePostalbox(): ?string
    {
        return $this->officePostalbox;
    }

    public function setOfficePostalbox(?string $officePostalbox): self
    {
        $this->officePostalbox = $officePostalbox;

        return $this;
    }

    public function getAnnexeStatus(): ?File
    {
        return $this->annexeStatus;
    }

    public function setAnnexeStatus(?File $annexeStatus): self
    {
        $this->annexeStatus = $annexeStatus;

        return $this;
    }

    public function getAnnexeReport(): ?File
    {
        return $this->annexeReport;
    }

    public function setAnnexeReport(?File $annexeReport): self
    {
        $this->annexeReport = $annexeReport;

        return $this;
    }

    public function getAnnexeAccount(): ?File
    {
        return $this->annexeAccount;
    }

    public function setAnnexeAccount(?File $annexeAccount): self
    {
        $this->annexeAccount = $annexeAccount;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(User $contact): self
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts[] = $contact;
            $contact->setOrganization($this);
        }

        return $this;
    }

    public function removeContact(User $contact): self
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getOrganization() === $this) {
                $contact->setOrganization(null);
            }
        }

        return $this;
    }

    public function getRepresentative(): ?User
    {
        return $this->representative;
    }

    public function setRepresentative(?User $representative): self
    {
        $this->representative = $representative;

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
    
    public function toArray()
    {
        return get_object_vars($this);
    }

    public function getIsConfirm(): ?bool
    {
        return $this->isConfirm;
    }

    public function setIsConfirm(bool $isConfirm): self
    {
        $this->isConfirm = $isConfirm;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getOldFiles(): Collection
    {
        return $this->oldFiles;
    }

    public function addOldFile(File $oldFile): self
    {
        if (!$this->oldFiles->contains($oldFile)) {
            $this->oldFiles[] = $oldFile;
            $oldFile->setOrganization($this);
        }

        return $this;
    }

    public function removeOldFile(File $oldFile): self
    {
        if ($this->oldFiles->removeElement($oldFile)) {
            // set the owning side to null (unless already changed)
            if ($oldFile->getOrganization() === $this) {
                $oldFile->setOrganization(null);
            }
        }

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

}

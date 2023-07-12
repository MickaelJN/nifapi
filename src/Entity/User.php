<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping\OrderBy;


/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @UniqueEntity(fields={"email"}, message="There is already an account with this email")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"project:read", "user:read","organizationfull:read", "projectfull:read", "projectmessage:read", "usershort:read", "log:read", "userlog:read", "organization:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"user:read","organizationfull:read", "projectfull:read", "usershort:read"})
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"user:read","organizationfull:read"})
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"project:read","user:read","organizationfull:read", "projectfull:read", "usershort:read", "log:read", "userlog:read", "organization:read"})
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"project:read", "user:read","organizationfull:read", "projectfull:read", "usershort:read", "log:read", "userlog:read", "organization:read"})
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Groups({"user:read", "projectmessage:read", "log:read", "userlog:read"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Groups({"user:read","organizationfull:read", "projectfull:read", "usershort:read"})
     */
    private $position;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Groups({"user:read","organizationfull:read"})
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=24, nullable=true)
     * @Groups({"user:read","organizationfull:read"})
     */
    private $mobile;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"user:read"})
     */
    private $isAdmin;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"user:read"})
     */
    private $isSecretariat;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"user:read"})
     */
    private $isSecretariatSupport;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"user:read"})
     */
    private $isFinance;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"user:read"})
     */
    private $isPresident;

    /**
     * @ORM\ManyToOne(targetEntity=File::class)
     * @Groups({"user:read"})
     */
    private $sign;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     *  @Groups({"user:read"})
     */
    private $defaultManager;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="contact")
     * @Groups({"user:read"})
     * @OrderBy({"number" = "DESC"})
     */
    private $contactProjects;
    
    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="manager")
     * @Groups({"user:read"})
     * @OrderBy({"number" = "DESC"})
     */
    private $managerProjects;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class, inversedBy="contacts")
     */
    private $organization;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     * @Groups({"user:read","organizationfull:read", "projectfull:read"})
     */
    private $gender;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $oldId;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $verifyCode;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $VerifyCodeDate;

    /**
     * @ORM\ManyToOne(targetEntity=File::class)
     * @Groups({"user:read"})
     */
    private $identityCard;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"user:read"})
     */
    private $identityCardValid;

    /**
     * @ORM\Column(type="date")
     */
    private $passwordValidity;

    public function __construct()
    {
        $this->contactProjects = new ArrayCollection();
        $this->managerProjects = new ArrayCollection();
        $this->identityCardValid = false;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = trim(mb_strtoupper($lastname));

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    public function setMobile(?string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }


    public function getIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(?bool $isAdmin): self
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getIsSecretariat(): ?bool
    {
        return $this->isSecretariat;
    }

    public function setIsSecretariat(?bool $isSecretariat): self
    {
        $this->isSecretariat = $isSecretariat;

        return $this;
    }

    public function getIsSecretariatSupport(): ?bool
    {
        return $this->isSecretariatSupport;
    }

    public function setIsSecretariatSupport(?bool $isSecretariatSupport): self
    {
        $this->isSecretariatSupport = $isSecretariatSupport;

        return $this;
    }

    public function getIsFinance(): ?bool
    {
        return $this->isFinance;
    }

    public function setIsFinance(?bool $isFinance): self
    {
        $this->isFinance = $isFinance;

        return $this;
    }

    public function getIsPresident(): ?bool
    {
        return $this->isPresident;
    }

    public function setIsPresident(?bool $isPresident): self
    {
        $this->isPresident = $isPresident;

        return $this;
    }

    public function getSign(): ?File
    {
        return $this->sign;
    }

    public function setSign(?File $sign): self
    {
        $this->sign = $sign;

        return $this;
    }

    public function isDefaultManager(): ?bool
    {
        return $this->defaultManager;
    }

    public function setDefaultManager(?bool $defaultManager): self
    {
        $this->defaultManager = $defaultManager;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getContactProjects(): Collection
    {
        return $this->contactProjects;
    }

    public function addContactProject(Project $project): self
    {
        if (!$this->contactProjects->contains($project)) {
            $this->contactProjects[] = $project;
            $project->setContact($this);
        }

        return $this;
    }

    public function removeContactProjects(Project $project): self
    {
        if ($this->contactProjects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getContact() === $this) {
                $project->setContact(null);
            }
        }

        return $this;
    }
    
    /**
     * @return Collection<int, Project>
     */
    public function getManagerProjects(): Collection
    {
        return $this->managerProjects;
    }

    public function addManagerProject(Project $project): self
    {
        if (!$this->managerProjects->contains($project)) {
            $this->managerProjects[] = $project;
            $project->setManager($this);
        }

        return $this;
    }

    public function removeManagerProjects(Project $project): self
    {
        if ($this->managerProjects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getManager() === $this) {
                $project->setManager(null);
            }
        }

        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(?int $gender): self
    {
        $this->gender = $gender;

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

    public function getVerifyCode(): ?string
    {
        return $this->verifyCode;
    }

    public function setVerifyCode(?string $verifyCode): self
    {
        $this->verifyCode = $verifyCode;

        return $this;
    }

    public function getVerifyCodeDate(): ?\DateTimeInterface
    {
        return $this->VerifyCodeDate;
    }

    public function setVerifyCodeDate(?\DateTimeInterface $VerifyCodeDate): self
    {
        $this->VerifyCodeDate = $VerifyCodeDate;

        return $this;
    }

    public function getIdentityCard(): ?File
    {
        return $this->identityCard;
    }

    public function setIdentityCard(?File $identityCard): self
    {
        $this->identityCard = $identityCard;

        return $this;
    }

    public function isIdentityCardValid(): ?bool
    {
        return $this->identityCardValid;
    }

    public function setIdentityCardValid(bool $identityCardValid): self
    {
        $this->identityCardValid = $identityCardValid;

        return $this;
    }

    public function getPasswordValidity(): ?\DateTimeInterface
    {
        return $this->passwordValidity;
    }

    public function setPasswordValidity(\DateTimeInterface $passwordValidity): self
    {
        $this->passwordValidity = $passwordValidity;

        return $this;
    }
}

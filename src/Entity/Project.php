<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping\OrderBy;
use App\Entity\Traits\ProjectTrait;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project {

    use ProjectTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="doctrine.uuid_generator")
     * @Groups({"project:read", "organization:read", "projectfull:read", "transferfull:read","user:read", "organizationfull:read", "projectmessage:read", "paymenttransfer:read", "log:read", "projectshort:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"project:read", "organization:read", "projectfull:read", "transferfull:read", "organizationfull:read", "user:read", "projectmessage:read", "paymenttransfer:read", "log:read", "projectshort:read", "projectwp:read"})
     */
    private $number;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class, inversedBy="projects")
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"project:read", "organization:read", "projectfull:read", "transferfull:read", "projectwp:read"})
     */
    private $organization;

    /**
     * @ORM\Column(type="string", length=250)
     * @Groups({"project:read", "organization:read", "projectfull:read", "transferfull:read", "user:read", "organizationfull:read", "projectmessage:read", "paymenttransfer:read", "log:read", "projectshort:read", "projectwp:read"})
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Secteur::class)
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $secteur;

    /**
     * @ORM\ManyToMany(targetEntity=Country::class)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $countries;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $dateBegin;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $dateEnd;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="managerProjects")
     * @Groups({"project:read", "projectfull:read", "projectmessage:read"})
     */
    private $manager;

    /**
     * @ORM\OneToOne(targetEntity=Phase::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     */
    private $phase;
    
    /**
     * @ORM\OneToOne(targetEntity=Phase::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     */
    private $phase1;

    /**
     * @ORM\OneToOne(targetEntity=Phase::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     */
    private $phase2;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read", "projectmessage:read"})
     */
    private $paymentType;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $percentage;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"project:read","projectfull:read", "user:read", "organizationfull:read", "projectwp:read"})
     */
    private $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $location;

    /**
     * @ORM\OneToMany(targetEntity=OrganizationLocal::class, mappedBy="project", orphanRemoval=true)
     * @Groups({"projectfull:read"})
     */
    private $locals;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $localAsk1;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $localAsk2;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $localAsk3;

    /**
     * @ORM\OneToMany(targetEntity=File::class, mappedBy="project")
     * @Groups({"projectfull:read"})
     */
    private $files;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $commentRefus;

    /**
     * @ORM\OneToOne(targetEntity=AllocatedAmount::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read","projectwp:read"})
     */
    private $initialAllocated;

    /**
     * @ORM\OneToMany(targetEntity=AllocatedAmount::class, mappedBy="project", cascade={"persist", "remove"}, orphanRemoval=true)
     * @Groups({"projectwp:read"})
     */
    private $extensions;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $paymentTerms;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="project", orphanRemoval=true, cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     * @OrderBy({"isReserve" = "ASC", "datePayment" = "ASC"})
     */
    private $payments;

    /**
     * @ORM\OneToMany(targetEntity=Invoice::class, mappedBy="project", cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     */
    private $invoices;

    /**
     * @ORM\Column(type="decimal", precision=10, scale=2, nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $percentageReserve;

    /**
     * @ORM\OneToOne(targetEntity=Report::class, cascade={"persist", "remove"})
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $finalReport;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="contactProjects", cascade={"persist", "remove"})
     * @Groups({"project:read", "projectfull:read", "transferfull:read", "projectmessage:read"})
     */
    private $contact;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"projectfull:read", "transferfull:read", "project:read"})
     */
    private $isContactValid;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"projectfull:read", "transferfull:read"})
     */
    private $contactValidationSend;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $contactValidationId;

    /**
     * @ORM\OneToOne(targetEntity=Refund::class, inversedBy="project", cascade={"persist", "remove"})
     * @Groups({"projectfull:read"})
     */
    private $refund;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"projectfull:read"})
     */
    private $fromSubscription;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $oldId;

    /**
     * @ORM\OneToOne(targetEntity=Message::class, cascade={"persist", "remove"})
     * @Groups({"projectmessage:read"})
     */
    private $messageManagerLastView;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"projectmessage:read", "projectfull:read"})
     */
    private $messageManagerNew;

    /**
     * @ORM\OneToOne(targetEntity=Message::class, cascade={"persist", "remove"})
     * @Groups({"projectmessage:read"})
     */
    private $messageContactLastView;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"projectmessage:read","projectfull:read"}) 
     */
    private $messageContactNew;

    /**
     * @ORM\OneToMany(targetEntity=Message::class, mappedBy="project", orphanRemoval=true)
     * @Groups({"projectmessage:read"})
     */
    private $messages;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $webTexte;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $webEvolution;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $dataWP = [];

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $webStatus = "draft";

    /**
     * @ORM\OneToMany(targetEntity=Photo::class, mappedBy="project", orphanRemoval=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     * @OrderBy({"position" = "ASC"})
     */
    private $photos;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"projectfull:read", "projectwp:read"})
     */
    private $webTexteComment;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"projectwp:read"})
     */
    private $updateWp;

    public function __construct() {
        $this->countries = new ArrayCollection();
        $this->locals = new ArrayCollection();
        $this->annexes = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->extensions = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->messageContactNew = 0;
        $this->messageManagerNew = 0;
        $this->webStatus = "draft";
        $this->messages = new ArrayCollection();
        $this->photos = new ArrayCollection();
    }

    public function getId(): ?Uuid {
        return $this->id;
    }

    public function getNumber(): ?int {
        return $this->number;
    }

    public function setNumber(?int $number): self {
        $this->number = $number;

        return $this;
    }

    public function getOrganization(): ?Organization {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self {
        $this->organization = $organization;

        return $this;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;

        return $this;
    }

    public function getSecteur(): ?Secteur {
        return $this->secteur;
    }

    public function setSecteur(?Secteur $secteur): self {
        $this->secteur = $secteur;

        return $this;
    }

    /**
     * @return Collection<int, Country>
     */
    public function getCountries(): Collection {
        return $this->countries;
    }

    public function addCountry(Country $country): self {
        if (!$this->countries->contains($country)) {
            $this->countries[] = $country;
        }

        return $this;
    }

    public function removeCountry(Country $country): self {
        $this->countries->removeElement($country);

        return $this;
    }

    public function getDateBegin(): ?\DateTimeInterface {
        return $this->dateBegin;
    }

    public function setDateBegin(?\DateTimeInterface $dateBegin): self {
        $this->dateBegin = $dateBegin;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): self {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getManager(): ?User {
        return $this->manager;
    }

    public function setManager(?User $manager): self {
        $this->manager = $manager;

        return $this;
    }
    
    public function getPhase(): ?Phase {
        return $this->phase;
    }

    public function setPhase(?Phase $phase): self {
        $this->phase = $phase;

        return $this;
    }

    public function getPhase1(): ?Phase {
        return $this->phase1;
    }

    public function setPhase1(?Phase $phase1): self {
        $this->phase1 = $phase1;

        return $this;
    }

    public function getPhase2(): ?Phase {
        return $this->phase2;
    }

    public function setPhase2(?Phase $phase2): self {
        $this->phase2 = $phase2;

        return $this;
    }

    public function getPaymentType(): ?string {
        return $this->paymentType;
    }

    public function setPaymentType(?string $paymentType): self {
        $this->paymentType = $paymentType;

        return $this;
    }

    public function getPercentage(): ?string {
        return $this->percentage;
    }

    public function setPercentage(?string $percentage): self {
        $this->percentage = $percentage;

        return $this;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function setStatus(?string $status): self {
        $this->status = $status;

        return $this;
    }

    public function getLocation(): ?string {
        return $this->location;
    }

    public function setLocation(?string $location): self {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, OrganizationLocal>
     */
    public function getLocals(): Collection {
        return $this->locals;
    }

    public function addLocal(OrganizationLocal $local): self {
        if (!$this->locals->contains($local)) {
            $this->locals[] = $local;
            $local->setProject($this);
        }

        return $this;
    }

    public function removeLocal(OrganizationLocal $local): self {
        if ($this->locals->removeElement($local)) {
            // set the owning side to null (unless already changed)
            if ($local->getProject() === $this) {
                $local->setProject(null);
            }
        }

        return $this;
    }

    public function getLocalAsk1(): ?string {
        return $this->localAsk1;
    }

    public function setLocalAsk1(?string $localAsk1): self {
        $this->localAsk1 = $localAsk1;

        return $this;
    }

    public function getLocalAsk2(): ?string {
        return $this->localAsk2;
    }

    public function setLocalAsk2(?string $localAsk2): self {
        $this->localAsk2 = $localAsk2;

        return $this;
    }

    public function getLocalAsk3(): ?string {
        return $this->localAsk3;
    }

    public function setLocalAsk3(?string $localAsk3): self {
        $this->localAsk3 = $localAsk3;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection {
        return $this->files;
    }

    public function addFile(File $file): self {
        if (!$this->files->contains($file)) {
            $this->files[] = $file;
            $file->setProject($this);
        }

        return $this;
    }

    public function removeFile(File $file): self {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getProject() === $this) {
                $file->setProject(null);
            }
        }

        return $this;
    }

    public function getCommentRefus(): ?string {
        return $this->commentRefus;
    }

    public function setCommentRefus(?string $commentRefus): self {
        $this->commentRefus = $commentRefus;

        return $this;
    }

    public function getInitialAllocated(): ?AllocatedAmount {
        return $this->initialAllocated;
    }

    public function setInitialAllocated(?AllocatedAmount $initialAllocated): self {
        $this->initialAllocated = $initialAllocated;

        return $this;
    }

    /**
     * @return Collection<int, AllocatedAmount>
     */
    public function getExtensions(): Collection {
        return $this->extensions;
    }

    public function addExtension(AllocatedAmount $extension): self {
        if (!$this->extensions->contains($extension)) {
            $this->extensions[] = $extension;
            $extension->setProject($this);
        }

        return $this;
    }

    public function removeExtension(AllocatedAmount $extension): self {
        if ($this->extensions->removeElement($extension)) {
            // set the owning side to null (unless already changed)
            if ($extension->getProject() === $this) {
                $extension->setProject(null);
            }
        }

        return $this;
    }

    public function getTotalAmount(): ?int {
        return $this->totalAmount;
    }

    public function setTotalAmount(?int $totalAmount): self {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getPaymentTerms(): ?string {
        return $this->paymentTerms;
    }

    public function setPaymentTerms(?string $paymentTerms): self {
        $this->paymentTerms = $paymentTerms;

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
            $payment->setProject($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): self {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getProject() === $this) {
                $payment->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): self {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices[] = $invoice;
            $invoice->setProject($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): self {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getProject() === $this) {
                $invoice->setProject(null);
            }
        }

        return $this;
    }

    public function getPercentageReserve(): ?string {
        return $this->percentageReserve;
    }

    public function setPercentageReserve(?string $percentageReserve): self {
        $this->percentageReserve = $percentageReserve;

        return $this;
    }

    public function getFinalReport(): ?Report {
        return $this->finalReport;
    }

    public function setFinalReport(?Report $finalReport): self {
        $this->finalReport = $finalReport;

        return $this;
    }

    public function getContact(): ?User {
        return $this->contact;
    }

    public function setContact(?User $contact): self {
        $this->contact = $contact;

        return $this;
    }

    public function getIsContactValid(): ?bool {
        return $this->isContactValid;
    }

    public function setIsContactValid(?bool $isContactValid): self {
        $this->isContactValid = $isContactValid;

        return $this;
    }

    public function getContactValidationSend(): ?\DateTimeInterface {
        return $this->contactValidationSend;
    }

    public function setContactValidationSend(?\DateTimeInterface $contactValidationSend): self {
        $this->contactValidationSend = $contactValidationSend;

        return $this;
    }

    public function getContactValidationId(): ?string {
        return $this->contactValidationId;
    }

    public function setContactValidationId(?string $contactValidationId): self {
        $this->contactValidationId = $contactValidationId;

        return $this;
    }

    public function getRefund(): ?Refund {
        return $this->refund;
    }

    public function setRefund(?Refund $refund): self {
        $this->refund = $refund;

        return $this;
    }

    public function getFinalPriseEnCharge() {
        return $this->getAlreadyPayed() - $this->getRefundAmountToPay();
    }

    public function getRefundAmountToPay() {
        if ($this->getRefund()) {
            return $this->getRefund()->getAmountToPay();
        }
        return 0.00;
    }

    public function isFromSubscription(): ?bool {
        return $this->fromSubscription;
    }

    public function setFromSubscription(?bool $fromSubscription): self {
        $this->fromSubscription = $fromSubscription;

        return $this;
    }

    public function getOldId(): ?int {
        return $this->oldId;
    }

    public function setOldId(?int $oldId): self {
        $this->oldId = $oldId;

        return $this;
    }

    public function getMessageManagerLastView(): ?Message {
        return $this->messageManagerLastView;
    }

    public function setMessageManagerLastView(?Message $messageManagerLastView): self {
        $this->messageManagerLastView = $messageManagerLastView;

        return $this;
    }

    public function getMessageManagerNew(): ?int {
        return $this->messageManagerNew;
    }

    public function setMessageManagerNew(?int $messageManagerNew): self {
        $this->messageManagerNew = $messageManagerNew;

        return $this;
    }

    public function getMessageContactLastView(): ?Message {
        return $this->messageContactLastView;
    }

    public function setMessageContactLastView(?Message $messageContactLastView): self {
        $this->messageContactLastView = $messageContactLastView;

        return $this;
    }

    public function getMessageContactNew(): ?int {
        return $this->messageContactNew;
    }

    public function setMessageContactNew(?int $messageContactNew): self {
        $this->messageContactNew = $messageContactNew;

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection {
        return $this->messages;
    }

    public function addMessage(Message $message): self {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setProject($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): self {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getProject() === $this) {
                $message->setProject(null);
            }
        }

        return $this;
    }

    public function getWebTexte(): ?string {
        return strip_tags(html_entity_decode($this->webTexte));
    }

    public function setWebTexte(?string $webTexte): self {
        $txt = htmlentities($webTexte, null, 'utf-8');
        $txt = htmlspecialchars_decode($txt);
        $this->webTexte = strip_tags(html_entity_decode($txt));

        return $this;
    }

    public function getWebEvolution(): ?string {
        return strip_tags(html_entity_decode($this->webEvolution));
    }

    public function setWebEvolution(?string $webEvolution): self {
        $txt = htmlentities($webEvolution, null, 'utf-8');
        $txt = htmlspecialchars_decode($txt);
        $this->webEvolution = strip_tags(html_entity_decode($txt));

        return $this;
    }

    public function getDataWP(): ?array {
        return $this->dataWP;
    }

    public function setDataWP(?array $dataWP): self {
        $this->dataWP = $dataWP;

        return $this;
    }

    public function getWebStatus(): ?string {
        return $this->webStatus;
    }

    public function setWebStatus(?string $webStatus): self {
        $this->webStatus = $webStatus;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): self {
        if (!$this->photos->contains($photo)) {
            $this->photos[] = $photo;
            $photo->setProject($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getProject() === $this) {
                $photo->setProject(null);
            }
        }

        return $this;
    }

    public function getWebTexteComment(): ?string
    {
        return $this->webTexteComment;
    }

    public function setWebTexteComment(?string $webTexteComment): self
    {
        $this->webTexteComment = $webTexteComment;

        return $this;
    }

    public function getUpdateWp(): ?bool
    {
        return $this->updateWp;
    }

    public function setUpdateWp(bool $updateWp): self
    {
        $this->updateWp = $updateWp;

        return $this;
    }

}

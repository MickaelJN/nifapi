<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File as EmailFile;
use App\Repository\UserRepository;
use App\Repository\PhotoRepository;
use App\Repository\ProjectRepository;
use App\Repository\OrganizationRepository;
use App\Repository\OrganizationLocalRepository;
use App\Repository\CountryRepository;
use App\Repository\SecteurRepository;
use App\Repository\PaymentRepository;
use App\Repository\RibRepository;
use App\Repository\FileRepository;
use App\Repository\AllocatedAmountRepository;
use App\Repository\AppParametersRepository;
use App\Repository\TransferRepository;
use App\Repository\InvoiceRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\ReportRepository;
use App\Repository\MessageRepository;
use App\Repository\LogActionRepository;
use App\Repository\LogApiRepository;
use App\Service\MPdfService;
use App\Service\LogService;
use App\Utils\MyUtils;
use App\Entity\File;
use App\Entity\Payment;
use App\Entity\Project;
use App\Entity\Refund;
use App\Entity\Transfer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

trait ControllerTrait {

    private $em;
    private $serializer;
    private $security;
    private $userRepository;
    private $projectRepository;
    private $organizationRepository;
    private $organizationLocalRepository;
    private $countryRepository;
    private $secteurRepository;
    private $paymentRepository;
    private $ribRepository;
    private $fileRepository;
    private $allocatedAmountRepository;
    private $appParametersRepository;
    private $transferRepository;
    private $invoiceRepository;
    private $subscriptionRepository;
    private $reportRepository;
    private $messageRepository;
    private $logActionRepository;
    private $logApiRepository;
    private $photoRepository;
    private $pdfService;
    private $logService;
    private $myUtils;
    private $parametersBag;
    private $userPasswordHasher;
    private $mailer;
    private $logs;
    private $finishedStatut;
    private $imagineCacheManager;

    public function __construct(EntityManagerInterface $em,
            SerializerInterface $serializer,
            Security $security,
            UserRepository $userRepository,
            ProjectRepository $projectRepository,
            OrganizationRepository $organizationRepository,
            OrganizationLocalRepository $organizationLocalRepository,
            CountryRepository $countryRepository,
            SecteurRepository $secteurRepository,
            PaymentRepository $paymentRepository,
            RibRepository $ribRepository,
            FileRepository $fileRepository,
            AllocatedAmountRepository $allocatedAmountRepository,
            AppParametersRepository $appParametersRepository,
            TransferRepository $transferRepository,
            InvoiceRepository $invoiceRepository,
            SubscriptionRepository $subscriptionRepository,
            ReportRepository $reportRepository,
            MessageRepository $messageRepository,
            LogActionRepository $logActionRepository,
            LogApiRepository $logApiRepository,
            PhotoRepository $photoRepository,
            MPdfService $pdfService,
            LogService $logService,
            MyUtils $myUtils,
            ParameterBagInterface $parameterBag,
            UserPasswordHasherInterface $userPasswordHasher,
            MailerInterface $mailer,
            CacheManager $liipCache
    ) {
        $this->em = $em;
        $this->serializer = $serializer;
        $this->security = $security;
        $this->userRepository = $userRepository;
        $this->projectRepository = $projectRepository;
        $this->organizationRepository = $organizationRepository;
        $this->organizationLocalRepository = $organizationLocalRepository;
        $this->countryRepository = $countryRepository;
        $this->secteurRepository = $secteurRepository;
        $this->paymentRepository = $paymentRepository;
        $this->ribRepository = $ribRepository;
        $this->fileRepository = $fileRepository;
        $this->allocatedAmountRepository = $allocatedAmountRepository;
        $this->appParametersRepository = $appParametersRepository;
        $this->transferRepository = $transferRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->reportRepository = $reportRepository;
        $this->messageRepository = $messageRepository;
        $this->logActionRepository = $logActionRepository;
        $this->logApiRepository = $logApiRepository;
        $this->photoRepository = $photoRepository;
        $this->pdfService = $pdfService;
        $this->logService = $logService;
        $this->myUtils = $myUtils;
        $this->parameterBag = $parameterBag;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->mailer = $mailer;
        $this->logs = array();
        $this->finishedStatut = array("finished", "canceled", "refusal");
        $this->imagineCacheManager = $liipCache;
    }

    public function getDateNow() {
        $date = null;
        if ($this->parameterBag->get("fakedate") && $this->parameterBag->get("fakedate") == 1) {
            $fakedate = $this->appParametersRepository->findOneBy(array("name" => "fakedate"));
            $date = new \DateTime($fakedate->getData()["date"]);
            $date = $date->format("Y-m-d") . " " . date("H:i:s");
            $newFakedate = new \DateTime($date);
            return $newFakedate;
        }
        $date = new \DateTime();
        return $date;
    }

    public function getDateAddInterval($type, $nb) {
        $date = $this->getDateNow();
        $date->add(new \DateInterval('P' . $nb . $type));
        return $date;
    }

    public function getDateNowObject() {
        //if ($this->parameterBag->get("fakedate")) {
        $fakedate = $this->appParametersRepository->findOneBy(array("name" => "fakedate"));
        return $fakedate;
        //}
        //return null;
    }

    public function removeReserve($project) {
        $reserve = $this->paymentRepository->findOneBy(
                array(
                    "project" => $project,
                    "isReserve" => true
                )
        );
        if ($reserve) {
            $this->em->remove($reserve);
        }
    }

    public function getDefaultManager() {
        return $this->userRepository->findOneBy(array("defaultManager" => true));
    }

    public function getPresident() {
        return $this->userRepository->findOneBy(array("isPresident" => true));
    }

    public function updateReserveDate($project, $date) {
        $reserve = $this->paymentRepository->findOneBy(array("project" => $project, "isReserve" => true));
        if ($reserve) {
            return $this->updateReserveDateByReserve($reserve, $date);
        }
    }

    public function updateReserveDateByReserve($reserve, $date) {
        $reserve->setDatePayment($this->getReserveDateUpdated($date));
        $this->em->persist($reserve);
        return $reserve;
    }

    public function getReserveDateUpdated($newdate) {
        $date = clone $newdate;
        return $date->modify('last day of this month')->add(new \DateInterval('P57D'))->modify('last day of this month')->setTime(23, 59, 59);
        ;
    }

    public function removeAllPayments($project) {
        foreach ($project->getPayments() as $payment) {
            $this->em->remove($payment);
            $this->em->flush();
        }
    }

    /* public function generateReserve($project) {
      $payment = new Payment();
      $payment->setProject($project);
      $payment->setAmount($project->getAlreadyInReserve());
      $payment->setReserve(true);

      $datePayment = new \DateTime($date);
      if (date("d") >= 14) {
      $datePayment->add(new \DateInterval('P18D'));
      }
      $datePayment->modify('last day of this month');
      $datePayment->setTime(23, 59, 59);
      $payment->setDatePayment($datePayment);

      $em->persist($payment);
      } */

    /* genere la réserve et la met pour la date de fin de projet */

    public function generateReserveEndProject($project) {
        $reserve = $project->getPaymentReserve();
        if (!$reserve) {
            $reserve = new Payment();
        }
        $reserve->setProject($project);
        $reserve->setAmount($project->getAlreadyInReserve());
        $reserve->setReserve(true);
        $reserve->setDatePayment($this->getReserveDateUpdated($project->getDateEnd()));
        return $reserve;
    }

    public function usersAuthorizeToProject($project) {
        $user = $this->security->getUser();
        $contact = $project->getContact()->getId();
        $manager = $project->getManager()->getId();
        $representative = $project->getOrganization()->getRepresentative()->getId();

        return $user->getIsAdmin() || in_array($user->getId(), array($contact, $manager, $representative));
    }

    public function usersNifAuthorizeToProject($project) {
        $user = $this->security->getUser();
        $manager = $project->getManager()->getId();

        return $user->getIsAdmin() || in_array($user->getId(), array($manager));
    }

    public function usersNifAuthorizeToProjectOrSecretariat($project) {
        $user = $this->security->getUser();
        return $user->getIsSecretariat() || $user->getIsSecretariatSupport() || $this->usersNifAuthorizeToProject($project);
    }

    public function usersOrganizationAuthorizeToProject($project, $isValid) {
        $user = $this->security->getUser();
        $contact = $project->getContact()->getId();
        $representative = $project->getOrganization()->getRepresentative()->getId();
        if ($isValid) {
            return $user->getId() == $representative || ($user->getId() == $contact && $project->getIsContactValid());
        }
        return $user->getId() == $representative || $user->getId() == $contact && $project->getIsContactValid();
    }

    public function usersAuthorizeToOrganization($organization) {
        $user = $this->security->getUser();
        return $user->getType() !== "association" || $user->getOrganization()->getId() === $organization->getId();
    }

    public function successPutProject($project, $returnRessource = false, $code = 200) {
        $json = [];
        try {
            $this->em->flush();
            if ($returnRessource) {
                $this->em->refresh($project);
                $json = json_decode($this->serializer->serialize(
                                $project,
                                'json',
                                ['groups' => array("projectfull:read")]
                        ), true);
            }
        } catch (\Exception $e) {
            return $this->failReturn(400, null, $e->getMessage());
        }
        return $this->successReturn($json, $code);
    }

    public function successReturn($json = [], $code = 200) {
        if (!empty($this->logs)) {
            $logApi = $this->logService->addLogs($json, $code, $this->logs);
        }
        return $this->json($json, $code, []);
    }

    public function failReturn($code, $message = "Erreur lors de l'enregistrement", $detail = null) {
        $return = array("code" => $code, "message" => $message);
        if ($detail) {
            $return["detail"] = $detail;
        }
        $this->logService->addLogs($return, $code);
        return $this->json($return, $code, []);
    }

    public function changeProjectToStatus($project, $newStatus, $data = []) {

        switch ($newStatus) {
            case "phase_draft":
                if (array_key_exists("commentNif", $data)) {
                    $this->logs[] = array("type" => "project", "action" => "project_update_status_phase_draft_correction", "project" => $project, "data" => array("cause" => $data["commentNif"]));
                } else {
                    $this->logs[] = array("type" => "project", "action" => "project_update_status_phase_draft", "project" => $project);
                }
                break;

            case "phase_submission":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_phase_submission", "project" => $project);
                break;

            case "deliberation":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_deliberation", "project" => $project);
                break;

            case "configuration":
                if (!$project->getNumber()) {
                    $number = $this->projectRepository->findMaxNumberProject();
                    $number = ($number) ? (int) $number + 1 : 1;
                    $project->setNumber($number);
                }
                if (!$project->getIsContactValid()) {
                    if ($project->getContact()->getId() === $user->getOrganization()->getRepresentative()->getId()) {
                        $project->setIsContactValid(true);
                    } else {
                        $project->setContactValidationSend($this->getDateNow());
                        $project->setContactValidationId($this->myUtils->randomPassword(64, false));
                        $this->prepareEmailContactValidation($project);
                    }
                }
                $this->logs[] = array("type" => "project", "action" => "project_update_status_configuration", "project" => $project);
                break;

            case "in_progress":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_in_progress", "project" => $project);
                if ($project->getStatus() === "waiting_final_report" && $project->getPaymentType() === "invoice") {
                    $project->newAllRefusedAutoInvoice();
                }
                break;

            case "waiting_final_report":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_waiting_final_report", "project" => $project, "data" => $data);
                $reserve = $project->getPaymentReserve();
                if (!$reserve) {
                    $reserve = $this->generateReserveNextPayment($project);
                    $project->addPayment($reserve);
                }
                $refund = $project->getRefund();
                if ($refund) {
                    $reserve->setAmount($refund->getInitialReserve());
                }
                $project->setRefund(null);
                if ($project->getPaymentType() === "invoice") {
                    $project->refusedAllNewInvoice(false);
                } else {
                    $project->removePaymentNotInTransfer();
                }
                break;

            case "waiting_reserve":
                $reserve = $project->getPaymentReserve();
                if(!$reserve){
                    $reserve = $this->generateReserveNextPayment($project);
                }
                if ($reserve && $reserve->getAmount() >= 0) {
                    $this->logs[] = array("type" => "project", "action" => "project_update_status_waiting_reserve", "project" => $project);
                } else {
                    if ($reserve) {
                        $this->em->remove($reserve);
                    }
                    $newStatus = "finished";
                    $this->logs[] = array("type" => "project", "action" => "project_update_status_finished", "project" => $project);
                }
                break;

            case "waiting_refund":
                $this->logs[] = array("type" => "project", "action" => "project_add_refund", "project" => $project);
                $refund = new Refund();
                $refund->setAmount($data["refundAmount"]);
                $refund->setInitialReserve($project->getAlreadyInReserve());
                if ($project->getAlreadyInReserve() >= $data["refundAmount"]) {
                    $newStatus = "waiting_reserve";
                    $reserve = $this->generateReserveNextPayment($project);
                    // on réduit le montant du transfer
                    $newReserve = $reserve->getAmount() - round($data["refundAmount"], 2);
                    if ($newReserve > 0) {
                        $reserve->setAmount($newReserve);
                        $this->em->persist($reserve);
                        $refund->setAmountToPay(0);
                        $refund->setJustification($data["refundJustification"]);
                        $this->logs[] = array("type" => "project", "action" => "project_refund_reduce_reserve", "project" => $project);
                        $this->logs[] = array("type" => "project", "action" => "project_update_status_waiting_reserve", "project" => $project);
                    } else {
                        $this->em->remove($reserve);
                        $newStatus = "finished";
                        $this->logs[] = array("type" => "project", "action" => "project_update_status_finished", "project" => $project);
                    }
                } else {
                    if ($project->getAlreadyInReserve() == $data["refundAmount"]) {
                        $newStatus = "finished";
                        $refund->setAmountToPay(0);
                        $reserve = $project->getPaymentReserve();
                        if ($reserve) {
                            $this->em->remove($reserve);
                        }
                        $this->logs[] = array("type" => "project", "action" => "project_refund_reduce_reserve", "project" => $project);
                        $this->logs[] = array("type" => "project", "action" => "project_update_status_finished", "project" => $project);
                    } else {
                        $refundAmount = round($data["refundAmount"], 2) - $project->getAlreadyInReserve();
                        $refund->setAmountToPay($refundAmount);
                        $this->logs[] = array("type" => "project", "action" => "project_refund_ask_refund", "project" => $project);
                        $this->logs[] = array("type" => "project", "action" => "project_update_status_waiting_refund", "project" => $project, "data" => $data);
                    }
                    $refund->setJustification($data["refundJustification"]);
                    $refund->setDateAsk($this->getDateNow());
                    $project->removePaymentReserve();
                }
                $project->setRefund($refund);
                break;

            case "refusal":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_refusal", "project" => $project, "data" => $data["commentNif"]);
                $project->setCommentRefus($data["commentNif"]);
                $project->setWebStatus("draft");
                break;

            case "canceled":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_canceled", "project" => $project, "data" => $data["commentNif"]);
                $project->setCommentRefus($data["commentNif"]);
                $project->setWebStatus("draft");
                break;

            case "finished":
                $this->logs[] = array("type" => "project", "action" => "project_update_status_finished", "project" => $project);
                break;

            default:
                break;
        }

        $project->setStatus($newStatus);
        $project->setUpdateWp(true);
    }

    /*
     *  place la réserve au prochain transfert et si elle n'existe pas, elle est générée 
     */

    public function generateReserveNextPayment($project) {
        $payment = $this->paymentRepository->findOneBy(array("project" => $project, "isReserve" => true));
        if (!$payment) {
            $payment = new Payment();
            $payment->setProject($project);
            $payment->setAmount($project->getAlreadyInReserve());
            $payment->setReserve(true);
        }

        //ICI DATE COURANTE OU DATE DU DERNIER TRANSFERT
        $datePayment = $this->getDateNow();
        if (date("d") >= 14) {
            $datePayment->add(new \DateInterval('P18D'));
        }
        $datePayment->modify('last day of this month')->setTime(23, 59, 59);
        $payment->setDatePayment($datePayment);

        $this->em->persist($payment);
        return $payment;
    }

    public function updateDateEnd($project, $date) {
        $newDate = $date->modify('last day of this month')->setTime(23, 59, 59);
        ;
        $newDate2 = clone $newDate;
        if ($date > $this->getDateNow()) {
            $this->updateReserveDate($project, $newDate2);
            $project->setDateEnd($newDate);
        } else {
            return $this->json(["code" => 405, "message" => "Vous ne pouvez pas choisir une date de fin de projet dans le passé"], 405, []);
        }
    }

    public function updateProjectManager($project, $manager) {
        if ($manager) {
            if ($manager->getType() === "administrateur") {
                $project->setManager($manager);
            } else {
                return $this->json(["code" => 404, "message" => "Utilisateur inconnu"], 404, []);
            }
        } else {
            return $this->json(["code" => 404, "message" => "Utilisateur inconnu"], 404, []);
        }
    }

    public function updateProjectContact($project, $contact) {
        //ICI VERIFIER LES DR
        $user = $this->security->getUser();
        if ($contact) {
            if (($contact->getType() === "association" && $contact->getId() != $project->getContact()->getId()) || ($project->getOrganization()->getRepresentative() && $project->getOrganization()->getRepresentative()->getId() == $user->getId())) {
                if ($contact->getId() != $project->getContact()->getId()) {
                    $project->setContact($contact);
                    $this->logs[] = array("type" => "project", "action" => "project_update_contact", "project" => $project);
                }
                $project->setIsContactValid($contact->getId() === $project->getOrganization()->getRepresentative()->getId() || $project->getOrganization()->getRepresentative()->getId() == $user->getId());
                if (!$project->getIsContactValid()) {
                    $project->setContactValidationSend($this->getDateNow());
                    $project->setContactValidationId($this->myUtils->randomPassword(64, false));
                    $this->prepareEmailContactValidation($project);
                }
            } else {
                return $this->json(["code" => 404, "message" => "Utilisateur inconnu"], 404, []);
            }
        }
        return $this->json(["code" => 404, "message" => "Utilisateur inconnu"], 404, []);
    }

    public function generateInitialAllocatedData($project, $initialAllocated) {
        $dataJson = array();
        $dataJson["project"] = array(
            "number" => $project->getNumber(),
            "name" => $project->getName()
        );
        $dataJson["representant"] = array(
            "gender" => $project->getOrganization()->getRepresentative()->getGender(),
            "lastname" => $project->getOrganization()->getRepresentative()->getLastname(),
            "firstname" => $project->getOrganization()->getRepresentative()->getFirstname(),
            "position" => $project->getOrganization()->getRepresentative()->getPosition()
        );
        $dataJson["organization"] = array(
            "name" => $project->getOrganization()->getName(),
            "address" => $project->getOrganization()->getOfficeAddress(),
            "zipcode" => $project->getOrganization()->getOfficeZipcode(),
            "city" => $project->getOrganization()->getOfficeCity(),
            "country" => $project->getOrganization()->getOfficeCountry()->getName(),
            "postalbox" => $project->getOrganization()->getOfficePostalbox(),
            "legalStatus" => $project->getOrganization()->getLegalStatus()
        );
        $president = $this->getPresident();
        if ($president) {
            $dataJson["president"] = array(
                "lastname" => $president->getLastname(),
                "firstname" => $president->getFirstname(),
                "position" => $president->getPosition(),
                "sign" => $president->getSign()->getUrl(),
            );
        } else {
            return $this->json(["code" => 405, "message" => "Vous devez d'abord nommer un président !"], 405, []);
        }
        $initialAllocated->setData($dataJson);

        $project->setInitialAllocated($initialAllocated);
        $url = $this->myUtils->generateUniqueFileName();
        $fileName = $this->getParameter('filename_validation') . $project->getNumber();
        $this->pdfService->generatePdfValidation($project, $fileName, $url);
        $file = new File();
        $file->setName("Validation allocation");
        $file->setUrl($url . ".pdf");
        $file->setExtension("pdf");
        $file->setType("validation");
        $file->setSlug($fileName);
        $initialAllocated->setFile($file);

        //$this->em->persist($initialAllocated);
    }

    public function generateTransferByDate(int $year, string $month) {
        $hasTransfer = $this->transferRepository->findOneBy(array("year" => $year, "month" => $month));
        if ($hasTransfer) {
            return $this->json(["code" => 405, "message" => "Un transfer existe déjà"], 405, []);
        }

        // genere le nouveau transfer
        $transfer = new Transfer();
        $transfer->setStatus("new");
        $transfer->setYear($year);
        $transfer->setMonth($month);

        $datePayment = new \DateTime($year . "-" . $month . "-01");
        $datePayment->modify('last day of this month')->setTime(23, 59, 59);
        ;

        // genere les paiements par facture
        $projects = $this->projectRepository->findBy(array(
            "status" => array("in_progress")
        ));
        foreach ($projects as $project) {
            $hasValidRib = true;
            $hasValidStatus = true;
            $isConfirm = true;
            $hasPrevReceiptApprouved = true;
            $hasPrevReport = true;
            $hasPrevReportValid = true;
            $paymentBlocked = false;
            if (!$project->getOrganization()->getRib() || !$project->getOrganization()->getRib()->getIsValid()) {
                $hasValidRib = false;
            }
            $hasValidStatus = $project->getOrganization()->getAnnexeStatus() ? true : false;
            $isConfirm = $project->getOrganization()->getIsConfirm();
            $lastPaymentPayed = $this->paymentRepository->lastPaymentPayedByProject($project);
            if ($lastPaymentPayed && !$lastPaymentPayed->getReceiptValidDate()) {
                $hasPrevReceiptApprouved = false;
            }
            if ($project->getPaymentType() == "timeline" && $lastPaymentPayed && !$lastPaymentPayed->getReport()) {
                $hasPrevReport = false;
            }
            if ($project->getPaymentType() == "timeline" && $lastPaymentPayed && $lastPaymentPayed->getReport() && $lastPaymentPayed->getReport()->getStatus() != "valid") {
                $hasPrevReportValid = false;
            }

            if ($project->getPaymentType() == "timeline") {
                $payments = $this->paymentRepository->findBy(array(
                    "project" => $project,
                    "transfer" => null,
                    "datePayment" => $datePayment,
                    "isReserve" => false
                ));
                if ($payments) {
                    if ($hasValidRib && $hasValidStatus && $isConfirm && $hasPrevReceiptApprouved && $hasPrevReport && $hasPrevReportValid) {
                        foreach ($payments as $payment) {
                            $transfer->addPayment($payment);
                        }
                    } else {
                        $paymentBlocked = true;
                        foreach ($payments as $payment) {
                            $date = clone $payment->getDatePayment();
                            $date->add(new \DateInterval('P18D'));
                            $date->modify('last day of this month')->setTime(23, 59, 59);
                            $payment->setDatePayment($date);
                        }
                    }
                }
            } else {
                $amountPayment = 0.00;
                $payment = new Payment();
                $payment->setProject($project);
                $payment->setDatePayment($datePayment);
                $invoices = $this->invoiceRepository->findBy(array(
                    "status" => array("valid", "updated"),
                    "payment" => null,
                    "project" => $project
                ));
                foreach ($invoices as $invoice) {
                    $payment->addInvoice($invoice);
                    $amountPayment += $invoice->getAmountToPay();
                }
                if ($amountPayment > 0) {
                    if ($hasValidRib && $hasValidStatus && $isConfirm && $hasPrevReceiptApprouved /*&& $hasPrevReport && $hasPrevReportValid*/) {
                        $payment->setAmount($amountPayment);
                        $payment->setReserve(false);
                        $this->em->persist($payment);
                        $this->em->flush();
                        $transfer->addPayment($payment);
                    } else {
                        $paymentBlocked = true;
                    }
                }
            }
            if ($paymentBlocked) {
                // ICI on gère les différents logs d'exclusion
            }
        }

        // Reserve
        $projects = $this->projectRepository->findBy(array(
            "status" => "waiting_reserve"
        ));
        foreach ($projects as $project) {
            $reserve = $this->paymentRepository->findOneBy(array(
                "project" => $project,
                "isReserve" => true
            ));
            if (!$reserve->getTransfer()) {
                if ($reserve->getAmount() > 0) {
                    if (!$project->getOrganization()->getRib() || !$project->getOrganization()->getRib()->getIsValid() || $project->getOrganization()->getAnnexeStatus() == null || !$project->getOrganization()->getIsConfirm()) {
                        $datePayment = $payment->getDatePayment();
                        $datePayment->add(new \DateInterval('P18D'));
                        $datePayment->modify('last day of this month')->setTime(23, 59, 59);
                        $payment->setDatePayment($datePayment);
                        $this->em->persist($payment);
                    } else {
                        $transfer->addPayment($reserve);
                    }
                }
            }
        }

        $projects = $this->projectRepository->findBy(array(
            "status" => "configuration"
        ));
        foreach ($projects as $project) {
            $payments = $this->paymentRepository->findBy(array(
                "project" => $project,
                "transfer" => null,
                "datePayment" => $datePayment,
                "isReserve" => false
            ));
            foreach ($payments as $payment) {
                $this->em->remove($payment);
            }
        }

        // Reserve dont le projet est au statut rapport final dont la date est dans le passé
        $dateLimit = $this->getDateNow();
        $dateLimit->modify('last day of this month')->setTime(23, 59, 59);
        $dateNextMonth = $this->getDateNow();
        $dateNextMonth->add(new \DateInterval('P18D'));
        $dateNextMonth->modify('last day of this month')->setTime(23, 59, 59);
        $payments = $this->paymentRepository->getPaymentReserveWaitingReportInPast($dateLimit);
        foreach ($payments as $p) {
            $p->setDatePayment($dateNextMonth);
            $this->em->persist($p);
        }

        // on efface tous les paiements en configuration du mois (car ils n'auront jamais lieu)
        $this->removePaymentInPastInConfiguration($datePayment);

        if ($transfer->getPayments()) {
            $this->em->persist($transfer);
            $this->em->flush();

            $president = $this->userRepository->findOneBy(array("isPresident" => true));
            if ($president) {
                $this->sendMail(
                        $president->getEmail(),
                        "Virements du mois à confirmer",
                        "transfer_notif",
                        array("transfer" => $transfer),
                );
            }

            $this->logs[] = array("type" => "transfer", "action" => "transfer_generate", "transfer" => $transfer);

            return $this->json(["code" => 200, "message" => "Transfer généré"], 200, []);
        }


        return $this->json(["code" => 200, "message" => "Aucun transfer à généré"], 200, []);
    }

    public function removePaymentInPastInConfiguration($datePayment) {
        $projects = $this->projectRepository->findBy(array(
            "status" => array("configuration"),
        ));
        foreach ($projects as $project) {
            $payments = $this->paymentRepository->findBy(array(
                "project" => $project,
                "isReserve" => false,
                "datePayment" => $datePayment
            ));
            foreach ($payments as $payment) {
                $this->em->remove($payment);
            }
        }
    }

    public function askReportFinalIfNeed() {
        $projects = $this->projectRepository->findBy(array(
            "status" => "in_progress"
        ));
        $dateCourante = $this->getDateNow();
        foreach ($projects as $project) {
            if ($dateCourante > $project->getDateEnd()) {
                if ($project->getType() === "timeline") {
                    //changement de status du projet
                    $this->changeProjectToStatus($project, "waiting_final_report");
                } elseif ($project->getType() === "invoice") {
                    // décalage d'un mois de la date de fin de projet
                    $project->setDateEnd($dateCourante->modify('last day of this month')->setTime(23, 59, 59));
                }
            }
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            return $this->json(["code" => 400, "message" => "Erreur lors de l'enregistrement", "detail" => $e->getCode() . " - " . $e->getMessage()], 400, []);
        }
        return $this->json([], 200, []);
    }

    public function refusedAllNewInvoice($project, $last = false): self {
        $cause = "Le projet a quitté la phase en cours. Les factures restantes ont donc toutes été refusées.";
        if ($last) {
            $cause = "Aucune facture supplémentaire ne peut être acceptée car le projet a déjà consommé tout le montant alloué ou a été changé de statut par l'administrateur.";
        }
        $cpt = 0;
        foreach ($project->getInvoices() as $invoice) {
            if ($invoice->getStatus() === "new") {
                $invoice->setStatus("refused");
                $invoice->setDateDecision(new \DateTime());
                $invoice->setPercentage(null);
                $invoice->setAmountValid(null);
                $invoice->setAmountToPay(null);
                $invoice->setCause($cause);
                $invoice->setCauseAuto($last ? "last" : "changeStatus");
                $invoice->setReserve(null);
                $invoice->setReservePercentage(null);
                $invoice->setProject($project);
                $this->em->persist($invoice);
                $cpt++;
            }
        }
        if ($cpt > 0) {
            $this->logs[] = array("type" => "project", "action" => "project_refusedAllAuto_invoice", "project" => $project);
        }
    }

    public function newAllRefusedAutoInvoice($project): self {
        $cpt = 0;
        foreach ($project->getInvoices() as $invoice) {
            if ($invoice->getStatus() === "refused" && $invoice->getCauseAuto() !== null) {
                $invoice->setStatus("new");
                $invoice->setDateDecision(null);
                $invoice->setPercentage(null);
                $invoice->setAmountValid(null);
                $invoice->setAmountToPay(null);
                $invoice->setCause(null);
                $invoice->setCauseAuto(null);
                $invoice->setReserve(null);
                $invoice->setReservePercentage(null);
                $invoice->setProject($project);
                $this->em->persist($invoice);
                $cpt++;
            }
        }
        if ($cpt > 0) {
            $this->logs[] = array("type" => "project", "action" => "project_renewrefusedauto_invoice", "project" => $project);
        }
    }

    public function isDateEndInTheFuture($project) {
        $currentDate = $this->getDateNow();
        $endofmonth = clone $currentDate;
        $day = clone $currentDate;
        $day = $day->format("j");
        $endofmonth->modify('last day of this month');
        if (($day >= 14 && ($endofmonth < $project->getDateEnd())) || ($day < 14 && ($endofmonth <= $project->getDateEnd()))) {
            return true;
        }
        return false;
    }

    public function isProjectReadyToInProgress($project) {
        $erreur = [];
        if (!$this->isDateEndInTheFuture($project)) {
            $erreur[] = "Le projet doit avoit une date de fin projet postérieure à la fin du mois courant";
            return $erreur;
        }
        if (!$project->getOrganization()->getRib()->getIsValid()) {
            $erreur[] = "Le RIB de l'association doit être valide.";
            return $erreur;
        }
        if (!$project->getInitialAllocated() || ($project->getInitialAllocated() && !$project->getInitialAllocated()->getDateSign())) {
            $erreur[] = "Le projet doit avoir son montant initial de défini";
            return $erreur;
        }
        if (!$project->getPaymentType()) {
            $erreur[] = "Le type de paiement n'est pas défini pour ce projet";
            return $erreur;
        }
        return $erreur;
    }

    public function sendMail($to, $subject, $template, $data = [], $files = [], $cc = null) {

        if ($this->parameterBag->get("fakedate")) {
            $data["fake"] = true;
            $data["real_recipient"] = $to;
            $to = "contact@web-plus-sucre.fr";
            if ($cc) {
                $cc = "mickael.jacinto.nunes@gmail.com";
            }
        } else {
            $data["fake"] = false;
            if (!$cc) {
                $cc = "projet@fondation-nif.com";
            }
        }

        $data["plateforme_url"] = $this->parameterBag->get("plateforme_url");

        $email = (new TemplatedEmail())
                //->from(new Address($this->parameterBag->get("email_sender"), $this->params->get("website_name")))
                ->from(new Address("projet@fondation-nif.com", "FONDATION NIF"))
                ->to($to)
                ->subject($subject)
                ->htmlTemplate('mail/' . $template . '.html.twig')
                ->context($data);

        if ($cc) {
            $email->cc($cc);
        }

        if (!empty($files)) {
            foreach ($files as $file) {
                $email->attachFromPath($this->parameterBag->get('uploadfile_directory_root') . "/" . $file->getUrl(), $file->getSlug());
            }
        }

        $this->mailer->send($email);
    }

    public function prepareEmailContactValidation($project) {

        $this->sendMail(
                $project->getOrganization()->getRepresentative()->getEmail(),
                "Demande de confirmation du mandat de la personne de contact",
                "contact_validation",
                array("project" => $project)
        );
    }

    public function getPaymentsNeedReport($project) {
        $payments = array();
        if ($project->getPaymentType() === "timeline") {
            if ($project->getPayments() !== null) {
                $now = $this->getDateNow();
                $prevPayment = null;
                foreach ($project->getPayments() as $payment) {
                    if ($prevPayment && (!$prevPayment->getReport() || $prevPayment->getReport()->getStatus() == "refused") && $prevPayment->getTransfer() && $prevPayment->getTransfer()->getStatus() == "executed" && date_diff(new \DateTime(), $payment->getDatePayment())->format("%a") <= 31) {
                        $payments[] = $prevPayment;
                    }
                    $prevPayment = $payment;
                }
            }
        }
        return $payments;
    }

    public function sendAllEmailContactValidationByOrganization($organization) {
        $projects = $this->projectRepository->findBy(array("organization" => $organization));
        $representativeId = $organization->getRepresentative()->getId();
        foreach ($projects as $project) {
            if ($project->getStatus() != "refusal" && $project->getStatus() != "finished" && $project->getStatus() != "canceled") {
                if ($project->getContact()->getId() != $representativeId) {
                    $project->setIsContactValid(false);
                    $project->setContactValidationSend($this->getDateNow());
                    $project->setContactValidationId($this->myUtils->randomPassword(64, false));
                    $this->prepareEmailContactValidation($project);
                    $this->logs[] = array("type" => "project", "action" => "project_contact_send_validation", "project" => $project);
                } else {
                    $project->setContactValidationId(null);
                    $project->setIsContactValid(true);
                    $project->setContactValidationSend(null);
                }
            } else {
                $project->setIsContactValid(false);
            }
            $this->em->persist($project);
            $this->em->flush();
        }
    }

}

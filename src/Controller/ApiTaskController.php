<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Entity\Task;
use App\Entity\File;
use App\Repository\PaymentRepository;
use App\Service\MPdfService;
use App\Utils\MyUtils;

class ApiTaskController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/tasks", name="api_tasks_get", methods={"GET"})
     */
    public function getTaskByProject() {
        $user = $this->security->getUser();
        if ($user) {
            $tasks = array();
            $hasProjectCurrent = null;
            $hasProjectCurrentTimeline = 0;
            $hasProjectCurrentInvoice = 0;
            $currentContactOnProject = array();
            if ($user->getType() === "association") {
                if ($user->getId() === $user->getOrganization()->getRepresentative()->getId()) {
                    $hasProjectCurrent = $this->projectRepository->findBy(array("organization" => $user->getOrganization(), "status" => array("phase_draft", "phase_submission", "deliberation", "configuration", "in_progress", "waiting_final_report", "waiting_refund", "waiting_reserve")));
                    $currentContactOnProject = $this->projectRepository->findBy(array("contact" => $user, "status" => array("phase_draft", "phase_submission", "deliberation", "configuration", "in_progress", "waiting_final_report", "waiting_refund", "waiting_reserve")));
                } else {
                    $hasProjectCurrent = $this->projectRepository->findBy(array("contact" => $user, "status" => array("phase_draft", "phase_submission", "deliberation", "configuration", "in_progress", "waiting_final_report", "waiting_refund", "waiting_reserve")));
                    $currentContactOnProject = $hasProjectCurrent;
                }
            } else {
                $hasProjectCurrent = $this->projectRepository->findBy(array("manager" => $user, "status" => array("phase_draft", "phase_submission", "deliberation", "configuration", "in_progress", "waiting_final_report", "waiting_refund", "waiting_reserve")));
            }
            if ($hasProjectCurrent) {
                foreach ($hasProjectCurrent as $p) {
                    if ($p->getPaymentType() === "timeline") {
                        $hasProjectCurrentTimeline++;
                    } elseif ($p->getPaymentType() === "invoice") {
                        $hasProjectCurrentInvoice++;
                    }
                }
            }
            $tasks["hasProject"] = $hasProjectCurrent ? count($hasProjectCurrent) : 0;
            $tasks["hasProjectTimeline"] = $hasProjectCurrentTimeline;
            $tasks["hasProjectInvoice"] = $hasProjectCurrentInvoice;

            $tasks["messages"] = null;
            $dateLimit = $this->getDateNow();
            $dateLimit->sub(new \DateInterval('P14D'));
            if ($user->getType() === "association") {
                if ($user->getId() === $user->getOrganization()->getId()) {
                    $messages = $this->messageRepository->getLastMessagesByContact($user, $dateLimit);
                } else {
                    $messages = $this->messageRepository->getLastMessagesByRepresentative($user, $dateLimit);
                }
            } else {
                $messages = $this->messageRepository->getLastMessagesByManager($user, $dateLimit);
            }
            $tasks["messages"] = $messages;

            if ($user->getIsPresident() || $user->getIsAdmin()) {
                // liste des signatures en attente
                $toSigns = array();
                $initialAllocateds = $this->projectRepository->getInitialAllocatedToSign();
                foreach ($initialAllocateds as $project) {
                    $toSigns[] = array(
                        "project" => array(
                            "id" => $project->getId(),
                            "name" => $project->getName(),
                            "number" => $project->getNumber()
                        ),
                        "organization" => array(
                            "id" => $project->getOrganization()->getId(),
                            "name" => $project->getOrganization()->getName()
                        ),
                        "id" => $project->getInitialAllocated()->getId(),
                        "amount" => $project->getInitialAllocated()->getAmount(),
                        "file" => $project->getInitialAllocated()->getFile()->getId(),
                        "extension" => false
                    );
                }

                $extensions = $this->allocatedAmountRepository->getExtensionsToSign();
                foreach ($extensions as $extension) {
                    $toSigns[] = array(
                        "project" => array(
                            "id" => $extension->getProject()->getId(),
                            "name" => $extension->getProject()->getName(),
                            "number" => $extension->getProject()->getNumber()
                        ),
                        "organization" => array(
                            "id" => $extension->getProject()->getOrganization()->getId(),
                            "name" => $extension->getProject()->getOrganization()->getName()
                        ),
                        "id" => $extension->getId(),
                        "amount" => $extension->getAmount(),
                        "file" => $extension->getFile()->getId(),
                        "extension" => true
                    );
                }

                $tasks["presidentToSign"] = $toSigns;
            }

            if ($user->getIsSecretariat() || $user->getIsSecretariatSupport() || $user->getIsAdmin()) {
                // liste des signatures en attente
                $toSigns = array();
                $initialAllocateds = $this->projectRepository->getInitialAllocatedToCheck();
                foreach ($initialAllocateds as $project) {
                    $toSigns[] = array(
                        "project" => array(
                            "id" => $project->getId(),
                            "name" => $project->getName(),
                            "number" => $project->getNumber()
                        ),
                        "organization" => array(
                            "id" => $project->getOrganization()->getId(),
                            "name" => $project->getOrganization()->getName()
                        ),
                        "id" => $project->getInitialAllocated()->getId(),
                        "amount" => $project->getInitialAllocated()->getAmount(),
                        "file" => $project->getInitialAllocated()->getFile()->getId(),
                        "extension" => false
                    );
                }

                $extensions = $this->allocatedAmountRepository->getExtensionsToCheck();
                foreach ($extensions as $extension) {
                    $toSigns[] = array(
                        "project" => array(
                            "id" => $extension->getProject()->getId(),
                            "name" => $extension->getProject()->getName(),
                            "number" => $extension->getProject()->getNumber()
                        ),
                        "organization" => array(
                            "id" => $extension->getProject()->getOrganization()->getId(),
                            "name" => $extension->getProject()->getOrganization()->getName()
                        ),
                        "id" => $extension->getId(),
                        "amount" => $extension->getAmount(),
                        "file" => $extension->getFile()->getId(),
                        "extension" => true
                    );
                }

                $tasks["secretariatToCheck"] = $toSigns;
            }

            if ($user->getType() === "administrateur") {

                // etat du versement du mois
                $date = $this->getDateNow();
                $transfer = $this->transferRepository->findOneBy(array(), array("id" => "DESC"));
                if (!$transfer) {
                    $tasks["currentTransfer"] = null;
                } else {
                    $tasks["currentTransfer"] = array(
                        "id" => $transfer->getId(),
                        "amount" => $transfer->getAmount(),
                        "status" => $transfer->getStatus(),
                        "dateExecution" => $transfer->getDateExecution()
                    );
                }

                // listes des RIB à valider
                $ribs = null;
                if ($user->getIsAdmin()) {
                    $ribs = $this->organizationRepository->getAllRibNotValid();
                } else {
                    $ribs = $this->organizationRepository->getAllRibNotValidByManager($user);
                }
                $toValid = array();
                foreach ($ribs as $organization) {
                    $toValid[] = array(
                        "id" => $organization->getRib()->getId(),
                        "newRib" => $organization->getRib()->getNewRib(),
                        "organization" => array(
                            "id" => $organization->getId(),
                            "name" => $organization->getName()
                        )
                    );
                }
                $tasks["ribNotValid"] = $toValid;

                // listes des factures à valider
                $projects = null;
                if ($user->getIsAdmin()) {
                    $projects = $this->projectRepository->getProjectsWithInvoiceNotValid();
                } else {
                    $projects = $this->projectRepository->getProjectsWithInvoiceNotValidByManager($user);
                }
                $invoicesToValid = array();
                foreach ($projects as $project) {
                    $invoices = $this->invoiceRepository->findBy(array("status" => "new", "project" => $project), array("dateAdd" => "DESC"));
                    // $this->invoiceRepository->countInvoicesNotValidByProject($project)
                    $invoicesToValid[] = array(
                        "id" => $project->getId(),
                        "name" => $project->getName(),
                        "number" => $project->getNumber(),
                        "nb" => count($invoices),
                        "lastDate" => $invoices[0]->getDateAdd()
                    );
                }
                $lastDate = array_column($invoicesToValid, 'lastDate');
                array_multisort($lastDate, SORT_ASC, $invoicesToValid);
                $tasks["invoicesToValid"] = $invoicesToValid;

                // listes des rapport final à valider
                $projects = null;
                if ($user->getIsAdmin()) {
                    $projects = $this->projectRepository->getProjectsWithReportFinalNotValid();
                } else {
                    $projects = $this->projectRepository->getProjectsWithReportFinalNotValidByManager($user);
                }
                $reportFinalsToValid = array();
                foreach ($projects as $project) {
                    $reportFinalsToValid[] = array(
                        "id" => $project->getId(),
                        "name" => $project->getName(),
                        "number" => $project->getNumber(),
                        "date" => $project->getFinalReport()->getCreatedAt()
                    );
                }
                $date = array_column($reportFinalsToValid, 'date');
                array_multisort($date, SORT_ASC, $reportFinalsToValid);
                $tasks["reportFinalsToValid"] = $reportFinalsToValid;

                // listes des rapports intermédaires à valider
                $payments = null;
                if ($user->getIsAdmin()) {
                    $payments = $this->paymentRepository->getPaymentsWithReportNotValid();
                } else {
                    $payments = $this->paymentRepository->getPaymentsWithReportNotValidByManager($user);
                }
                $reportsToValid = array();
                foreach ($payments as $payment) {
                    $reportsToValid[] = array(
                        "id" => $payment->getProject()->getId(),
                        "name" => $payment->getProject()->getName(),
                        "number" => $payment->getProject()->getNumber(),
                        "date" => $payment->getReport()->getCreatedAt()
                    );
                }
                $date = array_column($reportsToValid, 'date');
                array_multisort($date, SORT_ASC, $reportsToValid);
                $tasks["reportsToValid"] = $reportsToValid;

                if ($user->getIsAdmin()) {
                    $subscriptionsNew = $this->subscriptionRepository->findBy(array("status" => "new"));
                    $subscriptionsNotRead = $this->subscriptionRepository->findBy(array("status" => "new", "alreadyRead" => false));
                    $tasks["subscriptionsNew"] = $subscriptionsNew ? count($subscriptionsNew) : 0;
                    $tasks["subscriptionsNotRead"] = $subscriptionsNotRead ? count($subscriptionsNotRead) : 0;
                }
            }

            // listes des payments avec recu non validés
            $payments = null;
            if ($user->getType() === "administrateur") {
                if ($user->getIsAdmin()) {
                    $payments = $this->paymentRepository->getPaymentsWithReceiptNotValid();
                } else {
                    $payments = $this->paymentRepository->getPaymentsWithReceiptNotValidByManager($user);
                }
            } else {
                if ($user->getOrganization()->getRepresentative()->getId() === $user->getId()) {
                    $payments = $this->paymentRepository->getPaymentsWithReceiptNotValidByOrganization($user->getOrganization());
                } else {
                    $payments = $this->paymentRepository->getPaymentsWithReceiptNotValidByContact($user);
                }
            }
            $receiptsToValid = array();
            foreach ($payments as $payment) {
                $receiptsToValid[] = array(
                    "id" => $payment->getProject()->getId(),
                    "projet" => $payment->getProject()->getId(),
                    "name" => $payment->getProject()->getName(),
                    "number" => $payment->getProject()->getNumber(),
                    "date" => $payment->getDatePayment(),
                    "file" => $payment->getReceipt()->getId(),
                    "payment" => $payment->getId(),
                );
            }
            $date = array_column($receiptsToValid, 'date');
            array_multisort($date, SORT_ASC, $receiptsToValid);
            $tasks["receiptsToValid"] = $receiptsToValid;

            if ($user->getType() === "association") {
                // liste des rapport à envoyer
                $reportsToSend = array();
                foreach ($hasProjectCurrent as $project) {
                    if ($user->getId() == $project->getContact()->getId() || $user->getId() == $project->getOrganization()->getRepresentative()->getId()) {
                        foreach ($this->getPaymentsNeedReport($project) as $payment) {
                            $date = $this->paymentRepository->nextPaymentByPayment($payment);
                            $reportsToSend[] = array(
                                "id" => $payment->getId(),
                                "project" => $payment->getProject()->getId(),
                                "name" => $payment->getProject()->getName(),
                                "number" => $payment->getProject()->getNumber(),
                                "date" => $payment->getDatePayment(),
                                "payment" => $payment->getId(),
                                "paymentDate" => $payment->getTransfer()->getDateExecution(),
                                "report" => $payment->getReport() ? $payment->getReport()->toArray() : null,
                                "deadline" => $date ? $date->getDatePayment()->format("Y-m") . "-14" : null,
                                "isFinal" => false
                            );
                        }
                    }
                }
                $tasks["reportsToSend"] = $reportsToSend;

                // liste des rapports finaux à envoyer
                $finalreportsToSend = array();
                foreach ($hasProjectCurrent as $project) {
                    if ($project->getStatus() == "waiting_final_report" && (!$project->getFinalReport() || $project->getFinalReport()->getStatus() == "refused") && ($user->getId() == $project->getContact()->getId() || $user->getId() == $project->getOrganization()->getRepresentative()->getId())) {
                        $date = $project->getPaymentReserve();
                        $finalreportsToSend[] = array(
                            "id" => $project->getId(),
                            "project" => $project->getId(),
                            "name" => $project->getName(),
                            "number" => $project->getNumber(),
                            "report" => $project->getFinalReport() ? $project->getFinalReport()->toArray() : null,
                            "deadline" => $date ? $date->getDatePayment()->format("Y-m") . "-12" : null,
                            "isFinal" => true
                        );
                    }
                }
                $tasks["finalreportsToSend"] = $finalreportsToSend;

                $organization = $user->getOrganization();
                $one_year = new \DateInterval("P1Y");
                $one_year->invert = 1;
                $one_year_ago = $this->getDateNow();
                $one_year_ago->add($one_year);
                $tasks["organization"] = array(
                    "id" => $organization->getId(),
                    "name" => $organization->getName(),
                    "status" => $organization->getAnnexeStatus() !== null ? true : false,
                    "statusOld" => $organization->getAnnexeStatus() !== null ? $organization->getAnnexeStatus()->getCreatedAt() <= $one_year_ago : false,
                    "representative" => array(
                        "id" => $organization->getRepresentative()->getId(),
                        "lastname" => $organization->getRepresentative()->getLastname(),
                        "firstname" => $organization->getRepresentative()->getFirstname(),
                        "position" => $organization->getRepresentative()->getPosition(),
                        "email" => $organization->getRepresentative()->getEmail(),
                        "emailvalid" => strpos($organization->getRepresentative()->getEmail(), "fakeemail.com") !== false
                    ),
                    "rib" => !$organization->getRib() ? false : array(
                "iban" => $organization->getRib()->getIban(),
                "bic" => $organization->getRib()->getBic(),
                "valid" => $organization->getRib()->getIsValid(),
                "newRib" => $organization->getRib()->getNewRib()
                    ),
                    "ribvalid" => ($organization->getRib() && $organization->getRib()->getIsValid()) ? true : false,
                    "newrib" => ($organization->getRib() && $organization->getRib()->getNewRib()) ? true : false,
                    "isConfirm" => $organization->getIsConfirm() ? true : false
                );

                $currentProjectContact = array();
                foreach ($currentContactOnProject as $project) {
                    $date = $project->getPaymentReserve();
                    $currentProjectContact[] = array(
                        "id" => $project->getId(),
                        "name" => $project->getName(),
                        "number" => $project->getNumber(),
                        "status" => $project->getStatus(),
                        "contactValid" => $project->getIsContactValid() || $user->getId() === $user->getOrganization()->getRepresentative()->getId()
                    );
                }
                $tasks["currentProjectContact"] = $currentProjectContact;

                if ($user->getOrganization() && $user->getOrganization()->getRepresentative() && $user->getOrganization()->getRepresentative()->getId() == $user->getId()) {
                    $toValidContactProjects = $this->projectRepository->findBy(array("organization" => $user->getOrganization(), "isContactValid" => false));
                    $ps = array();
                    foreach ($toValidContactProjects as $project) {
                        if ($project->getContact() && $project->getContact()->getId() != $user->getId() && $project->getStatus() != "canceled" && $project->getStatus() != "finished") {
                            $ps[] = array(
                                "id" => $project->getId(),
                                "name" => $project->getName(),
                                "number" => $project->getNumber(),
                                "contactId" => $project->getContact()->getId(),
                                "contactName" => $project->getContact()->getLastname() . " " . $project->getContact()->getFirstname()
                            );
                        }
                        if ($project->getContact() && $project->getContact()->getId() == $user->getId()) {
                            $project->setIsContactValid(true);
                            $this->em->persist($project);
                            $this->em->flush();
                        }
                    }
                    $tasks["toValidContactProjects"] = $ps;
                }
            }

            // listes des projets avec texte site web à rédiger
            if ($user->getIsAdmin()) {
                $projectWithoutTextes = $this->projectRepository->getProjectWithoutSiteTexte();
                $ps = array();
                foreach ($projectWithoutTextes as $project) {
                    $ps[] = array(
                        "id" => $project->getId(),
                        "name" => $project->getName(),
                        "number" => $project->getNumber(),
                        "webStatus" => $project->getWebStatus(),
                        "hasComment" => $project->getWebTexteComment() != null ? true : false
                    );
                }
                $tasks["webTexteToComplete"] = $ps;
            }

            // listes des projets avec photo offline
            if ($user->getIsAdmin()) {
                $projectWithOfflinePhoto = $this->projectRepository->getProjectsWithPhotoOfflineByUser($user);
                $ps = array();
                foreach ($projectWithOfflinePhoto as $project) {
                    $ps[] = array(
                        "id" => $project->getId(),
                        "name" => $project->getName(),
                        "number" => $project->getNumber(),
                        "isManager" => $project->getManager()->getId() == $user->getId()
                    );
                }
                $tasks["newPhoto"] = $ps;
            }

            // listes des pièces d'identité à valider
            if ($user->getIsAdmin()) {
                $identityToValids = $this->userRepository->getIdentityToCheck();
                $us = array();
                foreach ($identityToValids as $u) {
                    $us[] = array(
                        "id" => $u->getId(),
                        "lastname" => $u->getLastname(),
                        "firstname" => $u->getFirstname(),
                        "organization" => array(
                            "id" => $u->getOrganization()->getId(),
                            "name" => $u->getOrganization()->getName()
                        ),
                        "identityCard" => $u->getIdentityCard()->getId(),
                    );
                }
                $tasks["identityToCheck"] = $us;
            }

            // identité manquante
            if ($user->getType() === "association" && !$user->getIdentityCard()) {
                $tasks["identityToSend"] = true;
            } else {
                $tasks["identityToSend"] = false;
            }

            // identité representant
            if ($user->getType() === "association") {
                $representative = $user->getOrganization()->getRepresentative();
                if ($representative && !$representative->getIdentityCard()) {
                    $tasks["identityRepresentative"] = array(
                        "id" => $representative->getId(),
                        "lastname" => $representative->getLastname(),
                        "firstname" => $representative->getFirstname(),
                        "organization" => array(
                            "id" => $representative->getOrganization()->getId(),
                            "name" => $representative->getOrganization()->getName()
                        ),
                    );
                }
            }

            $tasks["passwordRenew"] = $user->getPasswordValidity() <= $this->getDateNow();

            // annexe status à renouvelé
            if ($user->getIsAdmin()) {
                $organizations = $this->organizationRepository->getOldStatus();
                $renew = array();
                foreach ($organizations as $o) {
                    $renew[] = array(
                        "id" => $o->getId(),
                        "name" => $o->getName(),
                        "isRenew" => $o->getAnnexeStatus() !== null
                    );
                }
                $tasks["annexeStatusRenew"] = $renew;
            }
        }

        $tasks["currentDate"] = $this->getDateNow();

        return $this->successReturn($tasks, 200);
    }

}

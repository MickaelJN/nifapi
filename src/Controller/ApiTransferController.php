<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\Transfer;
use App\Repository\TransferRepository;
use App\Entity\Payment;
use App\Entity\File;
use App\Repository\PaymentRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\MPdfService;
use App\Utils\MyUtils;

class ApiTransferController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/transfers", name="api_projects_get_all", methods={"GET"})
     */
    public function getTransfers(Request $request) {
        @ini_set("memory_limit", -1);
        $user = $this->security->getUser();
        $transfers = null;

        if ($user->getType() !== "association") {
            $transfers = $this->transferRepository->findBy(array(), array("dateExecution" => "DESC"));
            $json = json_decode($this->serializer->serialize(
                            $transfers,
                            'json',
                            ['groups' => array("transfer:read")]
            ));
        } else {
            $transfers = $this->paymentRepository->getPaymentByOrganization($user->getOrganization());
            $json = json_decode($this->serializer->serialize(
                            $transfers,
                            'json',
                            ['groups' => array("payment:read", "paymenttransfer:read")]
            ));
        }
        return $this->successReturn($json, 200);
    }

    /**
     * @Route("/api/transfers/month", name="api_projects_get_month", methods={"GET"})
     */
    public function getTransferCurrent(Request $request) {
        $date = $this->getDateNow();
        return $this->getTransferByDate($date->format("Y"), $date->format("m"), $request);
    }

    /**
     * @Route("/api/transfers/last", name="api_projects_get_last", methods={"GET"})
     */
    public function getTransferLast(Request $request) {
        $year = "";
        $month = "";
        $transfer = $this->transferRepository->findOneBy(array(), array("id" => "DESC"));
        if (!$transfer) {
            $date = $this->getDateNow();
            $year = $date->format("Y");
            $month = $date->format("m");
        } else {
            $year = $transfer->getYear();
            $month = $transfer->getMonth();
        }
        return $this->getTransferByDate($year, $month, $request);
    }

    /**
     * @Route("/api/transfers/estimations", name="api_projects_get_estimations", methods={"GET"})
     */
    public function getTransfersEstimations(Request $request) {
        return $this->getEstimations();
    }

    /**
     * @Route("/api/transfers/{year}/{month}", name="api_projects_get_current_bydate", methods={"GET"})
     */
    public function getTransferCurrentByDate($year, $month, Request $request) {
        return $this->getTransferByDate($year, $month, $request);
    }

    /**
     * @Route("/api/transfers/{id}", name="api_projects_get_by_id", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function getTransferById($id, Request $request) {
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            $transfer = $this->transferRepository->findOneBy(array("id" => $id));

            if ($transfer) {
                $json = json_decode($this->serializer->serialize(
                                $transfer,
                                'json',
                                ['groups' => array("transferfull:read")]
                ));
                return $this->successReturn($json, 200);
            }
            return $this->failReturn(400, "Aucun versement ne correspond à ce mois !");
        }
        return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
    }

    /**
     * @Route("/api/transfers/{year}/{month}", name="api_projects_get_by_month", methods={"GET"}, requirements={"year"="^[0-9]{4}$","month"="^[0-9]{2}$"})
     */
    public function getTransferByMonth($year, $month, Request $request) {
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            $transfer = $this->transferRepository->findOneBy(array("year" => $year, "month" => $month));
            if ($transfer) {
                $json = json_decode($this->serializer->serialize(
                                $transfer,
                                'json',
                                ['groups' => array("transferfull:read")]
                ));
                return $this->successReturn($json, 200);
            }
            return $this->failReturn(404, "Aucun versement ne correspond à ce mois !");
        }
        return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
    }

    /**
     * @Route("/api/transfers/{id}", name="api_transfer_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putTransferById($id, Request $request) {
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        if ($user->getType() !== "association") {
            $transfer = $this->transferRepository->findOneBy(array("id" => $id));
            if ($transfer) {
                try {
                    $updateXML = false;

                    if (array_key_exists("dayExecute", $data)) {
                        $date = $transfer->getYear() . "-" . $transfer->getMonth() . "-" . $data["dayExecute"];
                        $date = new \DateTime($date);
                        $transfer->setDateExecution($date);
                        $updateXML = true;
                        $this->logs[] = array("type" => "transfer", "action" => "transfer_change_date", "transfer" => $transfer);
                    }

                    if (array_key_exists("status", $data)) {
                        $transfer->setStatus($data["status"]);
                        $transfer->setAmount($transfer->calculTotalAmount());
                        if ($data["status"] === "executed") {
                            $this->logs[] = array("type" => "transfer", "action" => "transfer_executed", "transfer" => $transfer);
                            foreach ($transfer->getPayments() as $payment) {
                                $url = $this->myUtils->generateUniqueFileName();
                                $fileName = "NIF-recu-" . $payment->getProject()->getNumber() . "-" . $payment->getTransfer()->getYear() . "-" . $payment->getTransfer()->getMonth();
                                $this->pdfService->generatePDFRecu($payment, $fileName, $url);
                                $file = new File();
                                $file->setName("Validation allocation");
                                $file->setUrl($url . ".pdf");
                                $file->setExtension("pdf");
                                $file->setType("validation");
                                $file->setSlug($fileName);
                                $payment->setReceipt($file);
                                $payment->setDatePayment($transfer->getDateExecution());
                                $this->changeStatusAfterTransferValidation($payment);
                                $this->em->persist($payment);
                                $this->logs[] = array("type" => "project", "action" => "project_add_receipt", "project" => $payment->getProject());
                            }
                        } else {
                            $this->logs[] = array("type" => "transfer", "action" => "transfer_status_" . $data["status"], "transfer" => $transfer);
                            $updateXML = true;
                        }
                    }

                    $this->em->persist($transfer);
                    $this->em->flush();
                    $this->em->refresh($transfer);

                    if ($updateXML && ($transfer->getStatus() == "valid" || $transfer->getStatus() != "transfer")) {
                        foreach ($transfer->getPayments() as $payment) {
                            // condition sur country car les anciens RIB n'ont pas de pays
                            $rib = array(
                                "iban" => $payment->getProject()->getOrganization()->getRib()->getIban(),
                                "bic" => $payment->getProject()->getOrganization()->getRib()->getBic(),
                                "bank" => $payment->getProject()->getOrganization()->getRib()->getBank(),
                                "address" => $payment->getProject()->getOrganization()->getRib()->getAddress(),
                                "isSepa" => $payment->getProject()->getOrganization()->getRib()->getIsSepa(),
                                "country" => !$payment->getProject()->getOrganization()->getRib()->getCountry() ?
                                null :
                                array(
                            "name" => $payment->getProject()->getOrganization()->getRib()->getCountry()->getName(),
                            "isocode2" => $payment->getProject()->getOrganization()->getRib()->getCountry()->getIsocode2())
                            );
                            $payment->setRibData($rib);
                            $this->em->persist($payment);
                            $this->em->flush();
                        }
                        $this->em->refresh($transfer);
                        $this->generateTransferPdfAndXML($transfer);
                    }

                    $json = json_decode($this->serializer->serialize(
                                    $transfer,
                                    'json',
                                    ['groups' => array("transferfull:read")]
                    ));

                    if (array_key_exists("status", $data) && $transfer->getStatus() == "valid") {
                        $admins = $this->userRepository->findBy(array("type" => "administrateur", "isActive" => true));
                        foreach ($admins as $admin) {
                            if ($admin->getIsSecretariat() || $admin->getIsSecretariatSupport() || $admin->getIsFinance() || $admin->getIsAdmin()) {
                                $this->sendMail(
                                        $admin->getEmail(),
                                        "Virements à transférer vers Multiline - " . $transfer->getMonth() . "/" . $transfer->getYear(),
                                        "transfer_notif",
                                        array("transfer" => $transfer, "admin" => $admin),
                                );
                            }
                        }
                    } elseif (array_key_exists("status", $data) && $transfer->getStatus() == "transfer") {
                        $admins = $this->userRepository->findBy(array("type" => "administrateur", "isActive" => true));
                        foreach ($admins as $admin) {
                            if ($admin->getIsSecretariat() || $admin->getIsSecretariatSupport() || $admin->getIsFinance() || $admin->getIsAdmin() || $admin->getIsPresident()) {
                                $this->sendMail(
                                        $admin->getEmail(),
                                        "Bordereaux des versements à signer - " . $transfer->getMonth() . "/" . $transfer->getYear(),
                                        "transfer_notif",
                                        array("transfer" => $transfer, "admin" => $admin),
                                );
                            }
                        }
                    } elseif (array_key_exists("status", $data) && $transfer->getStatus() == "waiting_execution") {
                        $admins = $this->userRepository->findBy(array("type" => "administrateur", "isActive" => true));
                        foreach ($admins as $admin) {
                            if ($admin->getIsSecretariat() || $admin->getIsSecretariatSupport() || $admin->getIsFinance() || $admin->getIsAdmin()) {
                                $this->sendMail(
                                        $admin->getEmail(),
                                        "Le bordereaux a été signé - " . $transfer->getMonth() . "/" . $transfer->getYear(),
                                        "transfer_notif",
                                        array("transfer" => $transfer, "admin" => $admin, "newStatus" => true),
                                );
                            }
                        }
                    }

                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Aucun versement ne correspond à ce mois !");
        }
        return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
    }

    function generateTransferPdfAndXML($transfer) {
        $totalPayment = 0.00;
        $sepa = 0;
        $sepaAmount = 0.00;
        $nonSepa = 0;
        $nonSepaAmount = 0.00;
        $payments = $transfer->getPayments();
        foreach ($payments as $payment) {
            $totalPayment += $payment->getAmount();
            if ($payment->getProject()->getOrganization()->getRib()->getIsSepa()) {
                $sepaAmount += $payment->getAmount();
                $sepa++;
            } else {
                $nonSepaAmount += $payment->getAmount();
                $nonSepa++;
            }
        }

        if ($sepa) {
            $xmlContent = $this->renderView('xml/transfer.html.twig', array(
                'transfer' => $transfer,
                'isSepa' => true,
                'amount' => $sepaAmount,
                'nb' => $sepa
            ));

            $path = $this->getParameter('uploadfile_directory_root') . '/transfer-' . $transfer->getYear() . '-' . $transfer->getMonth() . '-sepa.xml';
            $fileSystem = new Filesystem();
            $fileSystem->dumpFile($path, $xmlContent);
        }

        if ($nonSepa) {
            $xmlContent = $this->renderView('xml/transfer.html.twig', array(
                'transfer' => $transfer,
                'isSepa' => false,
                'amount' => $nonSepaAmount,
                'nb' => $nonSepa
            ));

            $path = $this->getParameter('uploadfile_directory_root') . '/transfer-' . $transfer->getYear() . '-' . $transfer->getMonth() . '-nonsepa.xml';
            $fileSystem = new Filesystem();
            $fileSystem->dumpFile($path, $xmlContent);
        }

        $name = "NIF_virements_" . $transfer->getYear() . "-" . $transfer->getMonth();
        $this->pdfService->generatePDFTransfer($transfer, $name, $name);
    }

    public function getTransferByDate($year, $month, Request $request) {
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            $transfer = $this->transferRepository->findOneBy(array("year" => $year, "month" => $month));
            if ($transfer) {
                $json = json_decode($this->serializer->serialize(
                                $transfer,
                                'json',
                                ['groups' => array("transferfull:read")]
                ));
                return $this->successReturn($json, 200);
            } else {
                return $this->getEstimationByMonth($year, $month, $request);
            }
        }
        return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
    }

    public function getEstimationByMonth($year, $month, Request $request) {
        try {
            $datePayment = new \DateTime($year . "-" . $month . "-01");
            $datePayment->modify('last day of this month');
            $datePayment->setTime(23, 59, 59);
            $invoicePayments = [];

            //if ($year == date("Y") && $month == date("m")) {
            // genere les paiements par facture
            $projects = $this->em->getRepository("App\Entity\Project")->findBy(array(
                "status" => array("in_progress"),
                "paymentType" => "invoice"
            ));
            foreach ($projects as $project) {
                $payment = array();
                $payment["project"] = $project;
                $payment["datePayment"] = $datePayment;

                $invoices = $this->em->getRepository("App\Entity\Invoice")->findBy(array(
                    "status" => array("valid", "updated", "new"),
                    "payment" => null,
                    "project" => $project
                ));
                $amountPayment = 0.00;
                $amountPaymentNotSure = 0.00;
                $amountPaymentTotal = 0.00;
                $invoicesList = array();
                foreach ($invoices as $invoice) {
                    $invoicesList[] = $invoice;
                    if ($invoice->getStatus() !== "new") {
                        $amountPaymentTotal += $invoice->getAmountToPay();
                    }
                }
                $payment["invoices"] = $invoicesList;
                if ($amountPaymentTotal > 0) {
                    $payment["amount"] = $amountPaymentTotal;

                    $lastPaymentPayed = $this->paymentRepository->lastPaymentPayedByProject($project);
                    $payment["conditions"] = array(
                        "hasRib" => $project->getOrganization()->getRib() && $project->getOrganization()->getRib()->getIsValid(),
                        "hasStatus" => $project->getOrganization()->getAnnexeStatus(),
                        "isConfirm" => $project->getOrganization()->getIsConfirm(),
                        "prevReceipt" => !$lastPaymentPayed || $lastPaymentPayed->getReceiptValidDate()
                    );

                    $invoicePayments[] = $payment;
                }
            }

            $avancePayments = array();
            $avances = $this->paymentRepository->findBy(array(
                "transfer" => null,
                "datePayment" => $datePayment,
                "isReserve" => false
            ));
            foreach ($avances as $a) {
                if ($a->getProject()->getStatus() === "in_progress") {
                    $payment = array();
                    $payment["project"] = $a->getProject();
                    $payment["datePayment"] = $datePayment;
                    $payment["amount"] = $a->getAmount();

                    $lastPaymentPayed = $this->paymentRepository->lastPaymentPayedByProject($a->getProject());
                    $payment["conditions"] = array(
                        "hasRib" => $a->getProject()->getOrganization()->getRib() && $a->getProject()->getOrganization()->getRib()->getIsValid(),
                        "hasStatus" => $a->getProject()->getOrganization()->getAnnexeStatus(),
                        "isConfirm" => $a->getProject()->getOrganization()->getIsConfirm(),
                        "prevReceipt" => !$lastPaymentPayed || $lastPaymentPayed->getReceiptValidDate(),
                        "prevReport" => !$lastPaymentPayed || $lastPaymentPayed->getReport(),
                        "prevReportValid" => !$lastPaymentPayed || ($lastPaymentPayed->getReport() && $lastPaymentPayed->getReport()->getStatus() == "valid"),
                    );
                    $avancePayments[] = $payment;
                }
            }



            // Reserves
            $projects = $this->em->getRepository("App\Entity\Project")->findBy(array(
                "status" => array("waiting_reserve")
            ));
            $reservePayments = array();
            foreach ($projects as $project) {
                $reserve = $this->paymentRepository->findOneBy(array(
                    "project" => $project,
                    "isReserve" => true
                ));
                if (!$reserve->getTransfer()) {
                    $reserve->setDatePayment($datePayment);
                    $this->em->persist($reserve);
                    $payment = array();
                    $payment["project"] = $reserve->getProject();
                    $payment["datePayment"] = $datePayment;
                    $payment["amount"] = $reserve->getAmount();

                    $lastPaymentPayed = $this->paymentRepository->lastPaymentPayedByProject($reserve->getProject());
                    $payment["conditions"] = array(
                        "hasRib" => $reserve->getProject()->getOrganization()->getRib() && $reserve->getProject()->getOrganization()->getRib()->getIsValid(),
                        "hasStatus" => $reserve->getProject()->getOrganization()->getAnnexeStatus(),
                        "isConfirm" => $reserve->getProject()->getOrganization()->getIsConfirm(),
                        "prevReceipt" => !$lastPaymentPayed || $lastPaymentPayed->getReceiptValidDate(),
                        "prevReport" => true,
                        "prevReportValid" => true,
                    );
                    $reservePayments[] = $payment;
                }
            }

            $transfer = array(
                "estimation" => true,
                "year" => $year,
                "month" => $month,
                "status" => "estimation",
                "avancePayments" => $avancePayments,
                "invoicePayments" => $invoicePayments,
                "reservePayments" => $reservePayments
            );
            $json = json_decode($this->serializer->serialize(
                            $transfer,
                            'json',
                            ['groups' => array("transferfull:read")]
                    ), true);

            $invoicePayments = [];
            $totalInvoice = 0.00;
            $totalInvoiceNotSure = 0.00;
            $totalAvance = 0.00;
            $totalNewInvoice = 0;
            foreach ($json["invoicePayments"] as $p) {
                $amountPaymentSure = 0.00;
                $amountPaymentNotSure = 0.00;
                $newInvoice = 0;
                foreach ($p["invoices"] as $i) {
                    if ($i["status"] == "new") {
                        $amountToPay = round(($i["initialAmount"] * $p["project"]["percentage"] / 100), 2);
                        $reserve = round(($amountToPay * $p["project"]["percentageReserve"] / 100), 2);
                        $amountToPay -= $reserve;
                        $amountPaymentNotSure += $amountToPay;
                        $newInvoice++;
                    } else {
                        $amountPaymentSure += $i["amountToPay"];
                    }
                }

                $totalInvoice += $amountPaymentSure;
                $totalNewInvoice += $newInvoice;
                $totalInvoiceNotSure += $amountPaymentNotSure;
                $p["amountPaymentNotSure"] = $amountPaymentNotSure;
                $p["newInvoice"] = $newInvoice;
                $invoicePayments[] = $p;
            }
            $json["invoicePayments"] = $invoicePayments;
            $json["totalInvoiceSure"] = $totalInvoice;
            $json["totalInvoiceNotSure"] = $totalInvoiceNotSure;
            $json["totalNewInvoice"] = $totalNewInvoice;

            $totalAvance = 0.00;
            foreach ($avancePayments as $p) {
                if (is_array($p)) {
                    $totalAvance += $p["amount"];
                } else {
                    $totalAvance += $p->getAmount();
                }
            }
            $json["totalAvance"] = $totalAvance;

            $totalReserve = 0.00;
            foreach ($reservePayments as $p) {
                if (is_array($p)) {
                    $totalReserve += $p["amount"];
                } else {
                    $totalReserve += $p->getAmount();
                }
            }
            $json["totalReserve"] = $totalReserve;
            $json["totalAmount"] = $totalAvance + $totalInvoice + $totalReserve;

            return $this->successReturn($json, 200);
        } catch (\Exception $e) {
            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
        }
    }

    /**
     * @Route("/api/transfers/estimations", name="api_projects_get_all_estimations", methods={"GET"})
     */
    public function getEstimations() {
        $payments = $this->paymentRepository->findBy(array("transfer" => null), array("datePayment" => "ASC"));

        $transfers = array();
        $monthCurrent = "";
        $monthPayments = array();
        $totalMonth = 0;
        foreach ($payments as $p) {
            if ($p->getProject()->getPaymentType() !== "invoice") {
                if ($monthCurrent != $p->getDatePayment()->format('Y-m')) {
                    if (count($monthPayments)) {
                        $transfers[] = array(
                            "month" => $monthCurrent,
                            "total" => $totalMonth,
                            "payments" => $monthPayments
                        );
                    }
                    $monthCurrent = $p->getDatePayment()->format('Y-m');
                    $monthPayments = array();
                    $totalMonth = 0;
                }
                $totalMonth += $p->getAmount();
                $monthPayments[] = array(
                    "id" => $p->getId(),
                    "isReserve" => $p->isReserve(),
                    "amount" => $p->getAmount(),
                    "project" => array(
                        "id" => $p->getProject()->getId(),
                        "number" => $p->getProject()->getNumber(),
                        "name" => $p->getProject()->getName()
                    ),
                    "organization" => array(
                        "id" => $p->getProject()->getOrganization()->getId(),
                        "name" => $p->getProject()->getOrganization()->getName()
                    )
                );
            }
        }
        if (count($monthPayments)) {
            $transfers[] = array(
                "month" => $monthCurrent,
                "total" => $totalMonth,
                "payments" => $monthPayments
            );
        }

        return $this->successReturn($transfers, 200);
    }

    public function changeStatusAfterTransferValidation($payment) {
        $project = $payment->getProject();
        $date = $this->getDateNow();
        if ($project->getStatus() === "in_progress") {
            if ($project->getPaymentType() === "invoice") {
                if ($project->getTotalAllocated() == ($project->getAlreadyInReserve() + $project->getAlreadyPayed())) {
                    //$project->setStatus("waiting_final_report");
                    $this->changeProjectToStatus($project, "waiting_final_report", []);
                    // ICI refuser toutes les factures encore en attente
                }
            } else {
                if ($project->getDateEnd() <= $date && $project->getTotalAllocated() == ($project->getTotalAllocated("reserve") + $project->getAlreadyPayed())) {
                    //$project->setStatus("waiting_final_report");
                    $this->changeProjectToStatus($project, "waiting_final_report", $data);
                }
            }
        } /* elseif ($project->getStatus() === "waiting_reserve") {
          if ($payment->isReserve()) {
          $project->setStatus("finished");
          }
          } */

        $dateCourante = $this->getDateNow();
        $dateCourante->modify('last day of this month');
        $object = $this->getDateNowObject();
        if ($object) {
            $newDate = $dateCourante->format("Y-m-d") . " " . date("H:i:s");
            $object->setData(array("date" => $newDate));
            $this->em->flush();
        }

        $this->em->persist($project);
        $this->em->flush();
    }

}

<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProjectNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface {

    private $normalizer;
    private $em;
    private $security;
    private $parameterBag;

    public function __construct(ObjectNormalizer $normalizer, EntityManagerInterface $em, Security $security, ParameterBagInterface $parameterBag) {
        $this->normalizer = $normalizer;
        $this->em = $em;
        $this->security = $security;
        $this->parameterBag = $parameterBag;
    }

    public function normalize($object, $format = null, array $context = []): array {
        $data = $this->normalizer->normalize($object, $format, $context);

        $groups = is_array($context['groups']) ? $context['groups'] : array($context['groups']);
        if (in_array("projectfull:read", $groups) || in_array("transferfull:read", $groups)) {
            $all = array();
            $tasks = array();

            $invoiceNextPaymentAmount = 0.00;
            $invoiceNextPaymentReserve = 0.00;
            $invoiceNextPaymentAmountTransfer = 0.00;
            $invoiceNextPaymentReserveTransfer = 0.00;
            $transferDate = null;
            $invoiceNew = [];
            $invoiceValid = [];
            $invoiceRefused = [];
            $invoicePayed = [];
            foreach ($object->getInvoices() as $invoice) {
                $i = array(
                    "id" => $invoice->getId(),
                    "initialAmount" => $invoice->getInitialAmount(),
                    "status" => $invoice->getStatus(),
                    "supplier" => $invoice->getSupplier(),
                    "proof" => array("id" => $invoice->getProof()->getId(), "url" => $invoice->getProof()->getUrl()),
                    "dateAdd" => $invoice->getDateAdd() ? $invoice->getDateAdd()->format('Y-m-d H:i:s') : null,
                    "amountToPay" => $invoice->getAmountToPay(),
                    "percentage" => $invoice->getPercentage(),
                    "amountValid" => $invoice->getAmountValid(),
                    "cause" => $invoice->getCause(),
                    "reserve" => $invoice->getReserve(),
                    "reservePercentage" => $invoice->getReservePercentage(),
                    "payment" => ($invoice->getPayment()) ? $invoice->getPayment()->getId() : null,
                    "transfer" => ($invoice->getPayment() && $invoice->getPayment()->getTransfer()) ? $invoice->getPayment()->getTransfer()->getId() : null,
                    "transferDate" => ($invoice->getPayment() && $invoice->getPayment()->getTransfer() && $invoice->getPayment()->getTransfer()->getDateExecution()) ? $invoice->getPayment()->getTransfer()->getDateExecution()->format('Y-m-d') : null,
                    "transferStatus" => ($invoice->getPayment() && $invoice->getPayment()->getTransfer()) ? $invoice->getPayment()->getTransfer()->getStatus() : null
                );

                if ($invoice->getPayment() && $invoice->getPayment()->getTransfer() && $invoice->getPayment()->getTransfer()->getStatus() === "executed") {
                    $invoicePayed[] = $i;
                } else {
                    if ($invoice->getStatus() === "new") {
                        $invoiceNew[] = $i;
                    } elseif ($invoice->getStatus() === "refused") {
                        $invoiceRefused[] = $i;
                    } elseif ($invoice->getStatus() === "valid" || $invoice->getStatus() === "updated") {
                        $invoiceValid[] = $i;
                        if ($i["transfer"] === null) {
                            $invoiceNextPaymentAmount += $i["amountToPay"];
                            $invoiceNextPaymentReserve += $i["reserve"];
                        } else {
                            $invoiceNextPaymentAmountTransfer += $i["amountToPay"];
                            $invoiceNextPaymentReserveTransfer += $i["reserve"];
                            $transferDate = $i["transferDate"] ? $i["transferDate"] : $transferDate;
                        }
                    }
                }
            }

            $data["invoices"] = [];
            $data["invoices"]["new"] = $invoiceNew;
            $data["invoices"]["valid"] = $invoiceValid;
            $data["invoices"]["refused"] = $invoiceRefused;
            $data["invoices"]["payed"] = $invoicePayed;

            $date = $this->getDateNow();
            if (date("d") >= 14) {
                $date->add(new \DateInterval('P18D'));
            }
            $date->modify('last day of this month');

            $data["data"] = array(
                "totalAllocated" => $object->getTotalAllocated(),
                "totalAllocatedReserve" => $object->getTotalAllocated("reserve"),
                "totalAllocatedWithoutReserve" => $object->getTotalAllocated("withoutReserve"),
                "alreadyPayed" => $object->getAlreadyPayed(),
                "notPayed" => $object->getTotalAllocated() - $object->getAlreadyPayed(),
                "alreadyInReserve" => $object->getAlreadyInReserve(),
                "maxPriseEnCharge" => $object->getMaxAmountPriseEnCharge(),
                "maxAmountToPay" => $object->getMaxAmountToPay(),
                "maxInvoiceValidation" => $object->getMaxAmountValid(),
                "alreadyAllocated" => $object->getAlreadyAllocated(),
                "finalPriseEnCharge" => $object->getFinalPriseEnCharge(),
                "invoiceNextPayment" => array(
                    "amount" => $invoiceNextPaymentAmount,
                    "reserve" => $invoiceNextPaymentReserve,
                    "amountTransfer" => $invoiceNextPaymentAmountTransfer,
                    "reserveTransfer" => $invoiceNextPaymentReserveTransfer,
                    "transferDate" => $transferDate,
                    "transferDateNext" => $date->format('Y-m-d')
                )
            );

            $extensionSign = [];
            $extensionNotSign = [];
            foreach ($object->getExtensions() as $extension) {
                $e = array(
                    "id" => $extension->getId(),
                    "amount" => $extension->getAmount(),
                    "dateAllocated" => $extension->getDateAllocated() ? $extension->getDateAllocated()->format('Y-m-d H:i:s') : null,
                    "reserve" => $extension->getReserve(),
                    "dateSign" => $extension->getDateSign() ? $extension->getDateSign()->format('Y-m-d H:i:s') : null,
                    "dateCheck" => $extension->getDateCheck() ? $extension->getDateCheck()->format('Y-m-d H:i:s') : null,
                    "data" => $extension->getData(),
                    "note" => $extension->getNote(),
                    "file" => $extension->getFile() ? array("id" => $extension->getFile()->getId()) : null
                );
                if ($extension->getDateSign()) {
                    $extensionSign[] = $e;
                } else {
                    $extensionNotSign[] = $e;
                }
            }
            $data["extensions"] = [];
            $data["extensions"]["sign"] = $extensionSign;
            $data["extensions"]["notSign"] = $extensionNotSign;

            $data["tasks"] = $this->getProjectTasks($object);
            if (count($extensionNotSign) > 0) {
                foreach ($extensionNotSign as $extension) {
                    $extension["type"] = "extensionnotsign";
                    $data["tasks"][] = $extension;
                }
            }
        } elseif (in_array("projectmessage:read", $groups)) {
            $data["lastMessage"] = $object->getLastMessage() ? $object->getLastMessage()->getId() : null;
        } elseif (in_array("projectwp:read", $groups)) {
            $data["totalAllocated"] = $object->getTotalAllocated();
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof \App\Entity\Project;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }

    public function getProjectTasks($object) {
        $tasks = array();

        // tâches concernant les paiements (reçu, rapport intermédiaire)
        foreach ($object->getPayments() as $payment) {
            if ($payment->getTransfer() && $payment->getTransfer()->getStatus() === "executed") {

                $p = array(
                    "id" => $payment->getId(),
                    "amount" => $payment->getAmount(),
                    "datePayment" => $payment->getDatePayment()->format('Y-m-d'),
                    "receipt" => $payment->getReceipt() ? $payment->getReceipt()->getId() : null,
                    "transfer" => array(
                        "id" => $payment->getTransfer()->getId(),
                        "dateExecution" => $payment->getTransfer()->getDateExecution() ? $payment->getTransfer()->getDateExecution()->format('Y-m-d') : null,
                        "year" => $payment->getTransfer()->getYear(),
                        "month" => $payment->getTransfer()->getMonth()
                    ),
                    "project" => array(
                        "id" => $payment->getProject()->getId(),
                        "number" => $payment->getProject()->getNumber(),
                    ),
                    "paymentType" => $payment->getProject()->getPaymentType()
                );
                $paymentNext = $this->em->getRepository("App\Entity\Payment")->nextPaymentByPayment($payment);
                if ($paymentNext) {
                    $p["paymentNext"] = array(
                        "id" => $paymentNext->getId(),
                        "datePayment" => $paymentNext->getDatePayment() ? $paymentNext->getDatePayment()->format('Y-m-d') : null,
                        "isReserve" => $paymentNext->isReserve(),
                        "days" => date_diff($this->getDateNow(), $paymentNext->getDatePayment())->format("%a")
                    );
                }

                if ($this->security->getUser()->getType() === "association" && ($this->security->getUser()->getId() === $object->getContact()->getId() || $this->security->getUser()->getId() === $object->getOrganization()->getRepresentative()->getId()) && !$payment->getReceiptValidDate()) {
                    $p["type"] = "receptNotValided";
                    $tasks[] = $p;
                }

                if ($this->security->getUser()->getType() === "association" && ($this->security->getUser()->getId() === $object->getContact()->getId() || $this->security->getUser()->getId() == $object->getOrganization()->getRepresentative()->getId()) && $object->getPaymentType() === "timeline" && !$payment->getReport() && $paymentNext && !$paymentNext->isReserve()) {
                    if (date_diff($this->getDateNow(), $paymentNext->getDatePayment())->format("%a") <= 31) {
                        $p["type"] = "reportNotSend";
                        $tasks[] = $p;
                    }
                }
                if ($object->getPaymentType() === "timeline" && $payment->getReport() && $payment->getReport() && ($payment->getReport()->getStatus() === "refused" || $payment->getReport()->getStatus() === "new")) {
                    $p["report"] = array(
                        "id" => $payment->getReport()->getId(),
                        "retard" => $payment->getReport()->isRetard(),
                        "newEndDate" => $payment->getReport()->getNewEndDate() ? $payment->getReport()->getNewEndDate()->format('Y-m-d') : null,
                        "problems" => $payment->getReport()->getproblems(),
                        "changeObjectif" => $payment->getReport()->isChangeObjectif(),
                        "changeObjectifDescription" => $payment->getReport()->getchangeObjectifDescription(),
                        "totalExpense" => $payment->getReport()->getTotalExpense(),
                        "comment" => $payment->getReport()->getComment(),
                        "status" => $payment->getReport()->getStatus(),
                        "refusDescription" => $payment->getReport()->getRefusDescription(),
                        "pdf" => array("id" => $payment->getReport()->getPdf()->getId(), "url" => $payment->getReport()->getPdf()->getUrl())
                    );
                    if ($payment->getReport()->getStatus() === "refused") {
                        $p["type"] = "reportRefused";
                        $tasks[] = $p;
                    }
                    if ($payment->getReport()->getStatus() === "new") {
                        $p["type"] = "reportNew";
                        $tasks[] = $p;
                    }
                }
            }
        }

        if ($object->getStatus() === "waiting_final_report") {
            // tâches si rapport final manquant
            if ($this->security->getUser()->getType() === "association" && ($this->security->getUser()->getId() === $object->getContact()->getId() || $this->security->getUser()->getId() == $object->getOrganization()->getRepresentative()->getId()) && (!$object->getFinalReport() || $object->getFinalReport()->getStatus() !== "valid")) {
                $tasks[] = array("type" => "taskReport");
            }

            // tâches si rapport final manquant coté admin
            elseif ($this->security->getUser()->getType() !== "association" && $this->security->getUser()->getIsAdmin() && !$object->getFinalReport()) {
                $tasks[] = array("type" => "taskReportAdmin");
            }

            // tâches si rapport à verifier
            elseif ($this->security->getUser()->getType() !== "association" && $this->security->getUser()->getIsAdmin() && $object->getFinalReport() && $object->getFinalReport()->getStatus() !== "valid") {
                $tasks[] = array("type" => "taskReport");
            }

            //decision remboursement ou non
            elseif ($this->security->getUser()->getType() !== "association" && $this->security->getUser()->getIsAdmin() && $object->getFinalReport() && $object->getFinalReport()->getStatus() === "valid") {
                $tasks[] = array("type" => "taskRefundDecision");
            }
        }

        if ($object->getStatus() === "waiting_refund") {
            $tasks[] = array("type" => "taskRefund");
        }
        
        // tâches concernant les textes du site vitrine
        if ($object->getWebStatus() == "pending" && $this->security->getUser()->getType() === "association" && ($this->security->getUser()->getId() === $object->getContact()->getId() || $this->security->getUser()->getId() == $object->getOrganization()->getRepresentative()->getId())) {
            $tasks[] = array("type" => "taskWordpressTexte");
        }
        if (($object->getStatus() == "in_progress" || $object->getStatus() == "waiting_refund" || $object->getStatus() == "waiting_final_report" || $object->getStatus() == "waiting_reserve") && $object->getWebStatus() == "draft" && $this->security->getUser()->getType() !== "association" && $this->security->getUser()->getIsAdmin()) {
            $tasks[] = array("type" => "taskWordpressTexte");
        }
        
        //si representant qui doit valider une personne de contact
        if($object->getIsContactValid() == false && $this->security->getUser()->getType() === "association" && $this->security->getUser()->getId() == $object->getOrganization()->getRepresentative()->getId()){
            $tasks[] = array("type" => "taskContactToValid");
        }
        

        return $tasks;
    }

    public function getDateNow() {
        $date = null;
        if ($this->parameterBag->get("fakedate")) {
            $fakedate = $this->em->getRepository("App\Entity\AppParameters")->findOneBy(array("name" => "fakedate"));
            $date = new \DateTime($fakedate->getData()["date"]);
            return $date;
        }
        $date = new \DateTime();
        return $date;
    }

}

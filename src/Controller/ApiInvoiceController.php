<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Entity\File;
use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Utils\MyUtils;

class ApiInvoiceController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/invoices", name="api_invoices_post", methods={"POST"})
     */
    public function postInvoice(Request $request) {
        $user = $this->security->getUser();
        $project = $this->projectRepository->findOneById($request->get("project"));
        $json = null;

        if ($project) {
            $invoice = new Invoice();
            $invoice->setSupplier($request->get("supplier"));
            $invoice->setInitialAmount($request->get("initialAmount"));
            $invoice->setDateAdd($this->getDateNow());
            $invoice->setStatus("new");

            $uploaded = $request->files->get('file');
            if (!is_null($uploaded)) {
                $fileName = $this->myUtils->generateUniqueFileName() . '.' . $uploaded->getClientOriginalExtension();
                $uploaded->move(
                        $this->getParameter('uploadfile_directory_root'),
                        $fileName
                );
                $file = new File();
                $file->setName($fileName);
                $file->setUrl($fileName);
                $file->setExtension($uploaded->getClientOriginalExtension());
                $file->setType("Invoice");
                $slug = "facture_P" . $project->getNumber();
                $file->setSlug($slug);
                $invoice->setProof($file);
            }

            try {
                $project->addInvoice($invoice);
                $this->em->persist($project);
                $this->em->flush();
                $json = json_decode($this->serializer->serialize(
                                $project,
                                'json',
                                ['groups' => array("projectfull:read")]
                ));
                $this->logs[] = array("type" => "project", "action" => "project_add_invoice", "project" => $project);
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->json(["code" => 404, "message" => "Erreur lors de l'enregistrement"], 404, []);
    }

    /**
     * @Route("/api/invoices/{id}", name="api_invoices_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putInvoice(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $last = false;

        if ($this->security->getUser()->getType() !== "association") {

            if (array_key_exists("status", $data)) {

                $invoice = $this->invoiceRepository->find($id);
                if ($invoice) {
                    if (!$invoice->getPayment() || ($invoice->getPayment() && $invoice->getPayment()->getTransfer() && $invoice->getPayment()->getTransfer()->getStatus() === "new")) {
                        try {
                            $hasPayment = $invoice->getPayment();
                            $startAmountToPay = $invoice->getAmountToPay();
                            $project = $invoice->getProject();
                            $maxAmountToPay = $project->getMaxAmountPriseEnCharge() + ($invoice->getPayment() ? $invoice->getAmountToPay() : 0);
                            $amountToPay = round(($invoice->getInitialAmount() * $project->getPercentage() / 100), 2);
                            $payment = $invoice->getPayment();

                            if ($data["status"] === "cancel") {
                                $invoice->setStatus("new");
                                $invoice->setDateDecision(null);
                                $invoice->setPercentage(null);
                                $invoice->setAmountValid(null);
                                $invoice->setAmountToPay(null);
                                $invoice->setCause(null);
                                $invoice->setReserve(null);
                                $invoice->setReservePercentage(null);
                                $invoice->setDateDecision(null);
                                $this->logs[] = array("type" => "project", "action" => "project_cancel_invoice", "project" => $project, array("invoice_id" => $invoice->getId(), "name" => $invoice->getSupplier()));
                            } else {
                                //ECHO $maxAmountToPay;
                                if ($amountToPay < $maxAmountToPay) {
                                    if ($data["status"] === "valid") {
                                        $invoice->setStatus("valid");
                                        $invoice->setPercentage($project->getPercentage());
                                        $invoice->setAmountValid($invoice->getInitialAmount());
                                        $amountToPay = round(($invoice->getInitialAmount() * $project->getPercentage() / 100), 2);
                                        $reserve = round(($amountToPay * $project->getPercentageReserve() / 100), 2);
                                        $amountToPay -= $reserve;
                                        $invoice->setAmountToPay($amountToPay);
                                        $invoice->setReserve($reserve);
                                        $invoice->setReservePercentage($project->getPercentageReserve());
                                        $invoice->setDateDecision($this->getDateNow());
                                        $this->logs[] = array("type" => "project", "action" => "project_valid_invoice", "project" => $project, array("invoice_id" => $invoice->getId(), "name" => $invoice->getSupplier()));
                                    }
                                } elseif ($maxAmountToPay > 0) {
                                    if ($data["status"] === "valid") {
                                        $data["status"] = "updated";
                                    }
                                    $last = true;
                                } else {
                                    $data["status"] = "refused";
                                    $last = true;
                                }

                                if ($data["status"] === "refused") {
                                    $invoice->setStatus("refused");
                                    $invoice->setDateDecision($this->getDateNow());
                                    $invoice->setPercentage(null);
                                    $invoice->setAmountValid(null);
                                    $invoice->setAmountToPay(null);
                                    $invoice->setCause($data["cause"]);
                                    $invoice->setReserve(null);
                                    $invoice->setReservePercentage(null);
                                    $this->logs[] = array("type" => "project", "action" => "project_refused_invoice", "project" => $project, array("invoice_id" => $invoice->getId(), "name" => $invoice->getSupplier()));
                                } elseif ($data["status"] === "updated") {
                                    $invoice->setPercentage($project->getPercentage());
                                    if ($last) {
                                        $amountValid = round($project->getMaxAmountValid(), 2);
                                        //$amountToPay = round($maxAmountToPay, 2);
                                        //$amountToPay = round(($amountValid * $project->getPercentage() / 100), 2);
                                        //$reserve = $project->getReserveReste();
                                        //$amountToPay -= $reserve;
                                        $invoice->setAmountValid($amountValid);
                                        $amountToPay = round(($project->getTotalAllocated("withoutReserve") - $project->getAlreadyAllocated() - $project->getAcceptedInvoiceNotInPayment("withoutReserve")), 2);
                                        $invoice->setAmountToPay($amountToPay);
                                        $invoice->setReserve($project->getReserveReste());
                                        $invoice->setCause("Le montant alloué étant atteint, la facture n'a pas pu être validée entièrement. Quelques arrondis ont pu être fait pour compléter le montant alloué et la réserve.");
                                    } else {
                                        $invoice->setStatus("updated");
                                        $amountValid = round($data["amountValid"], 2);
                                        $invoice->setAmountValid($amountValid);
                                        $amountToPay = (array_key_exists("amountToPay", $data)) ? $data["amountToPay"] : round(($amountValid * $project->getPercentage() / 100), 2);
                                        $reserve = round(($amountToPay * $project->getPercentageReserve() / 100), 2);
                                        $amountToPay -= $reserve;
                                        $invoice->setAmountToPay($amountToPay);
                                        $invoice->setReserve($reserve);
                                        $invoice->setCause($data["cause"]);
                                    }
                                    if ($last && $project->getPercentage() == 100.00 && $project->getTotalAllocated() == $invoice->getInitialAmount() && ($project->getAlreadyAllocated() == 0 && $project->getAcceptedInvoiceNotInPayment("withoutReserve") == 0)) {
                                        $invoice->setStatus("valid");
                                        $invoice->setAmountValid($invoice->getInitialAmount());
                                                $this->logs[] = array("type" => "project", "action"  => "project_valid_invoice", "project"  => $project, array("invoice_id" => $invoice->getId(), "name" => $invoice->getSupplier()));
                                    } else {
                                        $this->logs[] = array("type" => "project", "action" => "project_acceptwithmodification_invoice", "project" => $project, array("invoice_id" => $invoice->getId(), "name" => $invoice->getSupplier()));
                                        $invoice->setStatus("updated");
                                    }
                                    $invoice->setReservePercentage($project->getPercentageReserve());
                                    $invoice->setDateDecision($this->getDateNow());
                                }
                            }

                            if ($payment) {
                                if ($data["status"] === "cancel" || $data["status"] === "refused") {
                                    $payment->removeInvoice($invoice);
                                    //$invoice->setPayment(null);
                                    //$this->em->persist($payment);
                                }
                                $amountPayment = 0.00;
                                foreach ($payment->getInvoices() as $invoiceP) {
                                    $amountPayment += $invoiceP->getAmountToPay();
                                }
                                if ($amountPayment > 0) {
                                    $payment->setAmount($amountPayment);
                                    //$this->em->persist($payment);
                                } else {
                                    $this->em->remove($payment);
                                }
                            }

                            $invoice->setCauseAuto(null);

                            $this->em->flush();

                            // on refuse toutes les factures en attente
                            if ($last) {
                                $project->refusedAllNewInvoice(true);
                            } else {
                                if ($startAmountToPay > $invoice->getAmountToPay()) {
                                    $project->newAllRefusedAutoInvoice();
                                }
                            }

                            $this->em->flush();

                            $json = json_decode($this->serializer->serialize(
                                            $invoice,
                                            'json',
                                            ['groups' => 'projectfull:read']
                            ));
                            return $this->successReturn($json, 200);
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->json(["code" => 405, "message" => "Impossible de modifier une facture appartenant à un transfert déjà validé par le président"], 405, []);
                }
                return $this->json(["code" => 404, "message" => "Facture non trouvée"], 404, []);
            }
            return $this->json(["code" => 405, "message" => "Informations manquantes pour réaliser la demande"], 405, []);
        }
        return $this->json(["code" => 403, "message" => "Vous n'êtes pas autorisé à modifier cette information"], 403, []);
    }

    /**
     * @Route("/api/invoices/{id}", name="api_invoice_delete", methods={"DELETE"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function deleteInvoice(string $id, Request $request) {
        $json = null;
        $invoice = $this->invoiceRepository->findOneById($id);

        if ($invoice) {
            if ($invoice !== "new") {
                try {
                    $url = null;
                    $project = $invoice->getProject();
                    if ($invoice->getProof()) {
                        $url = $invoice->getProof()->getUrl();
                    }
                    $project->removeInvoice($invoice);
                    $this->em->persist($project);
                    $this->em->flush();

                    if ($url) {
                        @unlink($this->getParameter('uploadfile_directory_root') . "/" . $url);
                    }

                    $this->em->refresh($project);
                    $json = json_decode($this->serializer->serialize(
                                    $project,
                                    'json',
                                    ['groups' => array("projectfull:read", "secteur:read")]
                    ));
                    $this->logs[] = array("type" => "project", "action" => "project_remove_invoice", "project" => $project);
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            } else {
                return $this->failReturn(404, "Vous ne pouvez pas supprimer une facture qui a déjà été traité.");
            }
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement");
    }

}

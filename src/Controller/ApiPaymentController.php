<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Entity\Payment;
use App\Entity\File;
use App\Repository\PaymentRepository;
use App\Service\MPdfService;
use App\Utils\MyUtils;

class ApiPaymentController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/payments", name="api_payments_post", methods={"POST"})
     */
    public function postPayment(Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            if (array_key_exists("project", $data) && $data["project"] != "" && array_key_exists("datePayment", $data) && $data["datePayment"] !== "" && array_key_exists("amount", $data) && $data["amount"]) {
                $project = $this->projectRepository->findOneById($data["project"]);
                if ($project) {
                    if ($this->usersNifAuthorizeToProject($project)) {
                        if ($data["amount"] <= $project->getMaxAmountValid()) {
                            $payment = new Payment();
                            $payment->setAmount($data["amount"]);
                            $payment->setReserve(false);
                            $date = new \DateTime($data["datePayment"]);
                            $date->modify('last day of this month');
                            $now = $this->getDateNow();
                            $now->modify('last day of this month');
                            if ($date <= $project->getDateEnd()) {
                                if ($date->format("Y-m-d") >= $now->format("Y-m-d")) {
                                    if (!$this->paymentRepository->findOneBy(array("project" => $project, "datePayment" => $date))) {
                                        $payment->setDatePayment($date);
                                        $payment->setProject($project);
                                        try {
                                            $this->em->persist($payment);
                                            $this->em->flush();
                                            $this->logs[] = array("type" => "project", "action" => "project_add_payment", "project" => $project);
                                            return $this->successReturn($json, 200);
                                        } catch (\Exception $e) {
                                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                                        }
                                    }
                                    return $this->failReturn(400, "Vous ne pouvez pas ajouter 2 échéances pour un même mois !");
                                }
                                return $this->failReturn(400, "La date d'un paiement ne peut pas être antérieure à la fin du mois courant");
                            }
                            return $this->failReturn(400, "La date d'un paiement ne peut pas être postérieure à la date de fin de projet");
                        }
                        return $this->failReturn(400, "Le montant de ce paiement est supérieur au montant restant sur le projet");
                    }
                    return $this->failReturn(403, "Vous n'avez pas les droits pour ajouter un paiement !");
                }
                return $this->failReturn(404, "Projet non reconnu");
            }
            return $this->failReturn(400, "Informations incompléte");
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour ajouter des paiements !");
    }

    /**
     * @Route("/api/payments/{id}", name="api_payment_delete", methods={"DELETE"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function deletePayment(string $id, Request $request) {
        $json = null;
        $payment = $this->paymentRepository->findOneById($id);
        if ($payment) {
            $project = $payment->getProject();
            if ($this->usersNifAuthorizeToProject($project) || $user->getIsAdmin()) {
                if (!$payment->getTransfer() || $payment->getTransfer()->getStatus() === "new") {
                    if ($payment->getTransfer()) {
                        $payment->setTransfer(null);
                    }
                    try {
                        $project->removePayment($payment);
                        $this->em->persist($project);
                        $this->em->flush();
                        $this->logs[] = array("type" => "project", "action" => "project_remove_payment", "project" => $project);
                        return $this->successReturn($json, 200);
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(403, "Vous ne pouvez pas modifier un paiement déjà validé par le président");
            }
            return $this->failReturn(403, "Vous n'avez pas les droits pour modifier un paiement");
        }
        return $this->failReturn(404, "Paiement non trouvé");
    }

    /**
     * @Route("/api/payments/{id}", name="api_payments_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putPayment(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            $payment = $this->paymentRepository->find($id);
            if ($payment) {
                $project = $payment->getProject();
                if ($this->usersNifAuthorizeToProject($project)) {
                    if (!$payment->isReserve()) {
                        if (array_key_exists("amount", $data) && $data["amount"]) {
                            $montantrestant = $project->getMaxAmountValid() + $payment->getAmount();
                            if ($data["amount"] <= $montantrestant) {
                                $payment->setAmount($data["amount"]);
                            } else {
                                return $this->failReturn(400, "Le montant de ce paiement est supérieur au montant restant sur le projet");
                            }
                        } else {
                            return $this->failReturn(400, "Vous devez préciser le montant.");
                        }
                    }
                    if (array_key_exists("datePayment", $data) && $data["datePayment"] !== "") {
                        $date = new \DateTime($data["datePayment"]);
                        $date->modify('last day of this month');
                        $now = $this->getDateNow();
                        $now->modify('last day of this month');
                        if ($date <= $project->getDateEnd()) {
                            if ($date->format("Y-m-d") >= $now->format("Y-m-d")) {
                                if (!$this->paymentRepository->hasSameDate($payment->getProject(), $payment, $date)) {
                                    $payment->setDatePayment($date);
                                    $payment->setProject($project);
                                    try {
                                        $this->em->persist($payment);
                                        $this->em->flush();
                                        $this->logs[] = array("type" => "project", "action" => "project_update_payment", "project" => $project);
                                        return $this->successReturn($json, 200);
                                    } catch (\Exception $e) {
                                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                                    }
                                }
                                return $this->failReturn(400, "Vous ne pouvez pas ajouter 2 échéances pour un même mois !");
                            }
                            return $this->failReturn(400, "La date d'un paiement ne peut pas être antérieure à la fin du mois courant");
                        }
                        return $this->failReturn(400, "La date d'un paiement ne peut pas être postérieure à la date de fin de projet");
                    }
                    return $this->failReturn(400, "Vous devez préciser une date pour ce paiement");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour modifier un paiement !");
            }
            return $this->failReturn(404, "Paiement non existant");
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour ajouter des paiements !");
        return $this->json(["code" => 403, "message" => "Vous n'avez pas les droits pour ajouter des paiements !"], 403, []);
    }

    /**
     * @Route("/api/payments/{id}/recusign", name="api_payments_put_recusign", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putPaymentRecuSign(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $payment = $this->paymentRepository->find($id);
        if ($payment) {
            $project = $payment->getProject();
            if ($this->usersOrganizationAuthorizeToProject($project, true)) {
                if ($payment->getReceipt()) {
                    if (!$payment->getReceiptValidDate()) {
                        $payment->setReceiptValidDate(new \DateTime('now'));
                        $data = array(
                            "lastname" => $user->getLastname(),
                            "firstname" => $user->getFirstname(),
                            "position" => $user->getPosition(),
                        );
                        $payment->setReceiptData($data);
                        $url = $this->myUtils->generateUniqueFileName();
                        $fileName = "NIF-recu-" . $payment->getProject()->getNumber() . "-" . $payment->getTransfer()->getYear() . "-" . $payment->getTransfer()->getMonth();
                        $this->pdfService->generatePDFRecu($payment, $fileName, $url);
                        $file = new File();
                        $file->setName("Validation allocation");
                        $file->setUrl($url . ".pdf");
                        $file->setExtension("pdf");
                        $file->setType("receipt");
                        $file->setSlug($fileName);
                        $payment->setReceipt($file);
                        try {
                            $this->em->persist($payment);
                            $this->em->flush();
                            $this->logs[] = array("type" => "project", "action" => "project_receipt_sign", "project" => $project);
                            if ($project->getStatus() === "waiting_reserve") {
                                if ($payment->isReserve()) {
                                    $this->changeProjectToStatus($project, "finished");
                                }
                            }
                            return $this->successReturn($json, 200);
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Ce reçu est déjà signé");
                }
                return $this->failReturn(400, "Ce paiment n'a pas encore de reçu");
            }
            return $this->failReturn(403, "Vous n'avez pas les droits pour signer les reçus sur ce projet ! Vous n'êtes pas mandaté par le représentant légal. Veuillez lui demander de verifier ses emails.");
        }
        return $this->failReturn(404, "Payment non existant");
    }

}

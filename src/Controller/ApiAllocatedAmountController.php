<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\PaymentRepository;
use App\Entity\AllocatedAmount;
use App\Repository\AllocatedAmountRepository;
use App\Service\MPdfService;
use App\Utils\MyUtils;
use App\Entity\File;

class ApiAllocatedAmountController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/allocatedAmounts", name="api_allocatedAmounts_post", methods={"POST"})
     */
    public function postAllocatedAmount(Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            if (array_key_exists("project", $data)) {
                $project = $this->projectRepository->findOneById($data["project"]);
                if ($project) {
                    if ($project->getStatus() === "in_progress" || $project->getStatus() === "waiting_final_report") {
                        if ($this->usersNifAuthorizeToProject($project)) {
                            if (array_key_exists("amount", $data) && is_int($data["amount"]) && $data["amount"] >= 0) {
                                $extension = new AllocatedAmount();
                                $extension->setAmount($data["amount"]);
                                $reserve = round(($data["amount"] * $project->getPercentageReserve()), 2);
                                $extension->setReserve($reserve);
                                $extension->setDateAllocated(new \DateTime($data["dateAllocated"]));
                                if (array_key_exists("note", $data) && $data["note"] != "") {
                                    $extension->setNote($data["note"]);
                                } else {
                                    $extension->setNote(null);
                                }
                                $extension->setDateSign(null);
                                $extension->setDateCheck(null);
                                $cause = array_key_exists("cause", $data) ? $data["cause"] : null;
                                $this->generateExtensionData($project, $extension, false, $cause);
                                $this->logs[] = array("type" => "project", "action" => "project_extension_add", "project" => $project);
                                return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                            }
                            return $this->failReturn(400,"Informations incomplètes : date d'allocation et/ou montant");
                        }
                        return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
                    }
                    return $this->failReturn(400,"Une extension ne peut être ajoutée qu'en cours de projet ou lors de l'attente du rapport final.");
                }
                return $this->failReturn(404, "Aucun projet correspondant");
            }
            return $this->failReturn(400, "Informations incomplètes : date d'allocation et/ou montant");
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour créer une extension.");
    }

    /**
     * @Route("/api/allocatedAmounts/{id}/confirm", name="api_allocatedAmounts_put_confirm", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function putProjectExtensionConfirm($id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        $isSign = false;

        if ($user->getType() !== "association") {
            $extension = $this->allocatedAmountRepository->findOneById($id);
            if ($extension) {
                $project = $extension->getProject();
                if ($user->getType() !== "association" && ($user->getIsPresident() || $this->usersNifAuthorizeToProjectOrSecretariat($project))) {
                    if ($user->getIsPresident()) {
                        if (!$extension->getDateCheck()) {
                            $extension->setDateCheck($this->getDateNow());
                            $this->logs[] = array("type" => "project", "action" => "project_update_extension_confirm", "project" => $project);
                        }
                        if (!$extension->getDateSign()) {
                            $extension->setDateSign($this->getDateNow());
                            $isSign = true;
                            $this->logs[] = array("type" => "project", "action" => "project_update_extension_sign", "project" => $project);
                        }
                    } else {
                        if (!$extension->getDateCheck()) {
                            $extension->setDateCheck($this->getDateNow());
                            $this->logs[] = array("type" => "project", "action" => "project_update_extension_confirm", "project" => $project);
                        }
                    }
                    $this->generateExtensionData($project, $extension, true);
                    if ($isSign) {
                        $project->setUpdateWp(true);
                        $this->sendMail(
                                $project->getOrganization()->getRepresentative()->getEmail(),
                                "Confirmation d'extension", 
                                "validation",
                                array("project" => $project, "type" => "extension"),
                                array($extension->getFile()),
                                ($project->getOrganization()->getRepresentative()->getEmail() !== $project->getContact()->getEmail()) ? $project->getContact()->getEmail() : null
                        );
                    }
                    $this->em->persist($extension);
                    if ($user->getIsPresident() && $project->getStatus() === "waiting_final_report") {
                        $error = $this->isProjectReadyToInProgress($project);
                        if (count($error) == 0) {
                            $this->changeProjectToStatus($project, "in_progress");
                        }
                        return $this->failReturn(400, $error[0]);
                    }
                    return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                } else {
                    return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
                }
            }
            return $this->failReturn(404, "Aucune extension correspondante");
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour créer une extension.");
    }

    /**
     * @Route("/api/allocatedAmounts/{id}", name="api_allocatedAmounts_put", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function putAllocatedAmount($id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            $extension = $this->allocatedAmountRepository->findOneById($id);
            if ($extension) {
                $project = $extension->getProject();
                if ($project->getStatus() === "in_progress" || $project->getStatus() === "waiting_final_report") {
                    if ($this->usersNifAuthorizeToProject($project)) {
                        if (array_key_exists("amount", $data) && is_int($data["amount"]) && $data["amount"] >= 0) {
                            $extension->setAmount($data["amount"]);
                            $reserve = round(($data["amount"] * ($project->getPercentageReserve() / 100)), 2);
                            $extension->setReserve($reserve);
                            $extension->setDateAllocated(new \DateTime($data["dateAllocated"]));
                            if (array_key_exists("note", $data) && $data["note"] != "") {
                                $extension->setNote($data["note"]);
                            } else {
                                $extension->setNote(null);
                            }
                            $extension->setDateSign(null);
                            $extension->setDateCheck(null);
                            $cause = array_key_exists("cause", $data) ? $data["cause"] : null;
                            $this->generateExtensionData($project, $extension, false, $cause);
                            $this->logs[] = array("type" => "project", "action" => "project_update_extension", "project" => $project);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        }
                        return $this->failReturn(400, "Informations incomplètes : date d'allocation et/ou montant");
                    }
                    return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
                }
                return $this->failReturn(404, "Une extension ne peut être ajoutée qu'en cours de projet ou lors de l'attente du rapport final.");
            }
            return $this->failReturn(404,"Aucun projet correspondant");
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour créer une extension.");
    }

    public function generateExtensionData($project, $extension, $isConfirm = true, $cause = null) {
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
        $president = $this->userRepository->findOneBy(array("isPresident" => true));
        if ($president) {
            $dataJson["president"] = array(
                "lastname" => $president->getLastname(),
                "firstname" => $president->getFirstname(),
                "position" => $president->getPosition(),
                "sign" => $president->getSign()->getUrl(),
            );
        }
        if (!$isConfirm && $cause) {
            if ($cause !== "") {
                $dataJson["cause"] = $cause;
            } else {
                $dataJson["cause"] = null;
            }
        } else {
            if (array_key_exists("cause", $extension->getData())) {
                $dataJson["cause"] = $extension->getData()["cause"];
            }
        }
        $extension->setData($dataJson);

        $extension->setProject($project);

        $this->em->persist($extension);
        $this->em->flush();
        $this->em->refresh($project);

        $url = $this->myUtils->generateUniqueFileName();
        $fileName = $this->getParameter('filename_extension') . $project->getNumber();
        $this->pdfService->generatePdfExtension($extension, $fileName, $url);
        $file = new File();
        $file->setName("Extension allocation");
        $file->setUrl($url . ".pdf");
        $file->setExtension("pdf");
        $file->setType("extension");
        $file->setSlug($fileName);
        $extension->setFile($file);

        $this->em->persist($extension);
    }

}

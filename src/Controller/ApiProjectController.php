<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Project;
use App\Entity\Phase;
use App\Entity\Payment;
use App\Entity\Refund;
use App\Entity\File;
use App\Entity\AllocatedAmount;

class ApiProjectController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/projects", name="api_projects_get", methods={"GET"})
     */
    public function getProjects(Request $request) {
        $user = $this->security->getUser();

        $projects = null;
        $json = null;
        $where = array();
        if ($user->getType() === "association") {
            if ($user->getOrganization()) {
                $where = array("organization" => $user->getOrganization());
                $projects = $this->projectRepository->findBy(array("organization" => $user->getOrganization()), array("number" => "DESC"));
                $json = json_decode($this->serializer->serialize(
                                $projects,
                                'json',
                                ['groups' => 'project:read']
                        ), true);
            }
        } else {
            if ($request->query->get("status") && $request->query->get("status") != "") {
                $where["status"] = $request->query->get("status");
            }
            $projects = $this->projectRepository->findBy($where, array("number" => "DESC"));
            $json = json_decode($this->serializer->serialize(
                            $projects,
                            'json',
                            ['groups' => 'project:read']
            ));
        }
        return $this->successReturn($json);
    }

    /**
     * @Route("/api/projects/{id}", name="api_projects_get_one", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function getProjectById(string $id) {
        $json = null;
        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            $user = $this->security->getUser();
            if (($user->getType() === "association" && $project->getOrganization() === $user->getOrganization()) || $user->getType() !== "association") {
                $json = json_decode($this->serializer->serialize(
                                $project,
                                'json',
                                ['groups' => array("projectfull:read", "secteur:read"), 'circular_reference_handler' => function ($object) {
                                        return $object->getId();
                                    }]
                        ), true);
                return $this->successReturn($json);
            }
            return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
        }
        return $this->json($json, 404, []);
    }

    /**
     * @Route("/api/projects", name="api_projects_post", methods={"POST"})
     */
    public function postProject(Request $request) {
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        if ($user->getType() === "association") {
            if (array_key_exists("name", $data)) {
                try {
                    $project = new Project();
                    $project->setFromSubscription(false);
                    $project->setName($data["name"]);
                    $project->setOrganization($user->getOrganization());
                    $project->setStatus("phase_draft");
                    $project->setManager($this->getDefaultManager());
                    $project->setContact($user);
                    $project->setUpdateWp(false);
                    $project->setIsContactValid($user->getId() === $user->getOrganization()->getRepresentative()->getId());
                    $this->em->persist($project);
                    $this->em->flush();
                    if (!$project->getIsContactValid()) {
                        $project->setContactValidationSend($this->getDateNow());
                        $project->setContactValidationId($this->myUtils->randomPassword(64, false));
                        $this->prepareEmailContactValidation($project);
                    }
                    $this->em->persist($project);
                    $this->em->flush();
                    $this->em->refresh($project);

                    $json = json_decode($this->serializer->serialize(
                                    $project,
                                    'json',
                                    ['groups' => 'project:read']
                            ), true);
                    $this->logs[] = array("type" => "project", "action" => "project_add", "project" => $project);
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour introduire un nouveau projet");
    }

    /**
     * @Route("/api/projects/{id}", name="api_projects_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProject(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $project = $this->projectRepository->findOneById($id);
        $dataLog["project"] = $project;
        if ($project) {
            try {
                if (array_key_exists("name", $data)) {
                    $project->setName($data["name"]);
                    $project->setUpdateWp(true);
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => array_key_exists("description", $data) ? "project_update_phase_presentation" : "project_update_name");
                }

                if (array_key_exists("secteur", $data)) {
                    $secteur = $this->secteurRepository->find($data["secteur"]);
                    $project->setUpdateWp(true);
                    $project->setSecteur($secteur);
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_secteur");
                }

                if (array_key_exists("dateBegin", $data) && is_string($data["dateBegin"])) {
                    $project->setUpdateWp(true);
                    $project->setDateBegin(new \DateTime($data["dateBegin"]));
                }

                if (array_key_exists("dateEnd", $data) && is_string($data["dateEnd"])) {
                    $project->setUpdateWp(true);
                    $this->updateDateEnd($project, new \DateTime($data["dateEnd"]));
                }

                if (array_key_exists("localAsk1", $data)) {
                    $project->setLocalAsk1($data["localAsk1"]);
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_phase_local");
                }

                if (array_key_exists("localAsk2", $data)) {
                    $project->setLocalAsk2($data["localAsk2"]);
                }

                if (array_key_exists("localAsk3", $data)) {
                    $project->setLocalAsk3($data["localAsk3"]);
                }

                if (array_key_exists("webTexte", $data)) {
                    $txt = htmlentities($data["webTexte"], null, 'utf-8');
                    $txt = htmlspecialchars_decode($txt);
                    $project->setWebTexte(strip_tags(html_entity_decode($txt)));
                    $project->setUpdateWp(true);
                    if ($project->getWebStatus() != "publish") {
                        $project->setWebStatus("pending");
                        $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_site_texte_tovalid");
                    } else {
                        $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_site_texte");
                    }
                    $project->setWebTexteComment(null);
                }

                if (array_key_exists("webEvolution", $data)) {
                    $project->setWebEvolution($data["webEvolution"]);
                    $project->setUpdateWp(true);
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_site_evolution");
                }

                if (array_key_exists("countries", $data)) {
                    foreach ($project->getCountries() as $c) {
                        $project->removeCountry($c);
                    }
                    foreach ($data["countries"] as $c) {
                        $country = $this->countryRepository->findOneByIsocode2($c["isocode2"]);
                        $project->addCountry($country);
                    }
                    $project->setUpdateWp(true);
                }

                if (array_key_exists("action", $data) && $data["action"] === "wordpress_texte_decision") {
                    if (array_key_exists("newWebStatus", $data) && $data["newWebStatus"] === "publish") {
                        $project->setWebStatus($data["newWebStatus"]);
                        $project->setWebTexteComment(null);
                        $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_site_accept");
                        // ici lancer envoi vers l'autre site ?
                    } elseif (array_key_exists("newWebStatus", $data) && $data["newWebStatus"] === "draft") {
                        $project->setWebStatus($data["newWebStatus"]);
                        if (array_key_exists("webTexteComment", $data)) {
                            $project->setWebTexteComment($data["webTexteComment"]);
                            $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_site_refus");
                        }
                    } else {
                        // erreur
                    }
                    $project->setUpdateWp(true);
                }

                $phase = $project->getPhase();
                $isPhaseUpdated = false;
                if ($phase === null) {
                    $phase = new Phase();
                }

                if (array_key_exists("description", $data)) {
                    $phase->setDescription($data["description"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("cause", $data)) {
                    $phase->setCause($data["cause"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("objectif", $data)) {
                    $phase->setObjectif($data["objectif"]);
                    $isPhaseUpdated = true;
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_phase_objectif");
                }

                if (array_key_exists("objectif2", $data)) {
                    $phase->setObjectif2($data["objectif2"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("resources", $data)) {
                    $phase->setResources($data["resources"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("beneficiary", $data)) {
                    $phase->setBeneficiary($data["beneficiary"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("cost", $data)) {
                    $phase->setCost($data["cost"]);
                    $isPhaseUpdated = true;
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_phase_funding");
                }

                if (array_key_exists("funding", $data)) {
                    $phase->setFunding($data["funding"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("solicitation", $data)) {
                    $phase->setSolicitation($data["solicitation"]);
                    $isPhaseUpdated = true;
                }

                if (array_key_exists("comment", $data)) {
                    $phase->setComment($data["comment"]);
                    $isPhaseUpdated = true;
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_phase_comment");
                }

                if (array_key_exists("duration", $data)) {
                    $phase->setDuration($data["duration"]);
                    $isPhaseUpdated = true;
                    $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_phase_duration");
                }

                if (array_key_exists("location", $data)) {
                    $project->setUpdateWp(true);
                    $phase->setLocation($data["location"]);
                    $isPhaseUpdated = true;
                    $project->setLocation($data["location"]);
                    if ($project->getStatus() == "phase_draft") {
                        $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_phase_location");
                    } else {
                        $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_update_location");
                    }
                }


                if ($isPhaseUpdated) {
                    $this->em->persist($phase);
                    $project->setPhase($phase);
                } else {
                    if (array_key_exists("location", $data)) {
                        $project->setLocation($data["location"]);
                    }
                }

                $this->em->persist($project);
                $this->em->flush();
                $this->em->refresh($project);

                return $this->successPutProject($project, array_key_exists("returnRessource", $data));
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Aucun projet correspondant : enregistrement impossible", null);
    }

    /**
     * @Route("/api/projects/{id}/date", name="api_projects_put_date", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectDate(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if ($user->getType() !== "association") {
            $project = $this->projectRepository->find($id);
            if ($project) {
                if ($user->getIsAdmin() || $user->getId() === $project->getManager()->getId()) {
                    $project->setDateBegin(new \DateTime($data["dateBegin"]));
                    if (array_key_exists("dateBegin", $data) && is_string($data["dateBegin"]) && array_key_exists("dateEnd", $data) && is_string($data["dateEnd"])) {
                        if (new \DateTime($data["dateBegin"]) <= new \DateTime($data["dateEnd"])) {
                            try {
                                $project->setDateBegin(new \DateTime($data["dateBegin"]));
                                $project->setUpdateWp(true);
                                $this->updateDateEnd($project, new \DateTime($data["dateEnd"]));
                                $this->logs[] = array("type" => "project", "action" => "project_update_date", "project" => $project);
                                return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                            } catch (\Exception $e) {
                                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                            }
                        }
                        return $this->failReturn(400, "La date de début de projet doit être antérieure à la date de fin de projet", null);
                    }
                    return $this->failReturn(400, "Données manquantes", null);
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour enregistrer cette information !", null);
            }
            return $this->failReturn(404, "Aucun projet correspondant", null);
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour enregistrer cette information !", null);
    }

    /**
     * @Route("/api/projects/{id}/manager", name="api_projects_put_manager", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectManager(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        if ($user->getType() !== "association" && $user->getIsAdmin()) {
            $project = $this->projectRepository->find($id);
            if ($project) {
                if (array_key_exists("manager", $data)) {
                    try {
                        $manager = $this->userRepository->find($data["manager"]);
                        $this->updateProjectManager($project, $manager);
                        $this->logs[] = array("type" => "project", "action" => "project_update_manager", "project" => $project);
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
            }
            return $this->failReturn(404, "Aucun projet correspondant", null);
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour changer de personne de suivi !", null);
    }

    /**
     * @Route("/api/projects/{id}/contact", name="api_projects_put_contact", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectContact(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $project = $this->projectRepository->find($id);
        if ($project) {
            if ($user->getType() !== "association" || $user->getOrganization()->getId() === $project->getOrganization()->getId()) {
                if (array_key_exists("contact", $data)) {
                    $contact = $this->userRepository->find($data["contact"]);
                    try {
                        if ($contact->getId() !== $project->getContact()->getId() || ($project->getOrganization()->getRepresentative() && $project->getOrganization()->getRepresentative()->getId() == $user->getId())) {
                            $this->updateProjectContact($project, $contact);
                            if ($contact->getId() === $project->getOrganization()->getRepresentative()->getId() || $project->getOrganization()->getRepresentative()->getId() == $user->getId()) {
                                $this->logs[] = array(
                                    "type" => "project",
                                    "project" => $project,
                                    "action" => "project_contact_validation",
                                    "data" => array(
                                        "contact" => array(
                                            "lastname" => $contact->getLastname(),
                                            "firstname" => $contact->getFirstname(),
                                            "id" => $contact->getId()
                                        )
                                    )
                                );
                            }
                        }
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
            }
            return $this->failReturn(403, "Vous n'avez pas les droits pour changer de personne de contact sur ce projet !", null);
        }
        return $this->failReturn(404, "Aucun projet correspondant", null);
    }

    /**
     * @Route("/api/projects/{id}/contactvalidationrelance", name="api_projects_put_contactvalidationrelance", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectContactValidationRelance(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $project = $this->projectRepository->find($id);
        if ($project) {
            if ($user->getType() !== "association" || $user->getOrganization()->getId() === $project->getOrganization()->getId()) {
                try {
                    $project->setIsContactValid(false);
                    $project->setContactValidationSend($this->getDateNow());
                    $project->setContactValidationId($this->myUtils->randomPassword(64, false));
                    $this->prepareEmailContactValidation($project);

                    $this->logs[] = array("type" => "project", "action" => "project_contact_relance_validation", "project" => $project);
                    return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(403, "Vous n'avez pas les droits pour changer de personne de contact sur ce projet !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/phase_submission", name="api_projects_status_put_phase_submission", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusPhaseSubmission(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "phase_draft") {
                if ($this->usersAuthorizeToProject($project)) {
                    try {
                        $this->changeProjectToStatus($project, "phase_submission", $data);
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/phase_draft", name="api_projects_status_put_phase_draft", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusPhaseDraft(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "phase_draft" || $project->getStatus() === "phase_submission") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    try {
                        if ($project->getStatus() === "phase_submission") {
                            if (array_key_exists("commentNif", $data) && $data["commentNif"] != "") {
                                $project->getPhase()->setCommentNif($data["commentNif"]);
                            } else {
                                return $this->failReturn(400, "Informations incomplète : vous devez laisser un commentaire pour indiquer les modifications");
                            }
                        }
                        $this->changeProjectToStatus($project, "phase_draft", $data);
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/deliberation", name="api_projects_status_put_deliberation", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusDeliberation(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "phase_submission") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    try {
                        $this->changeProjectToStatus($project, "deliberation", $data);
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(404, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/configuration", name="api_projects_status_put_configuration", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusConfiguration(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "deliberation") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    try {
                        $this->changeProjectToStatus($project, "configuration", $data);
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(404, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/refusal", name="api_projects_status_put_refusal", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusRefusal(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "phase_submission") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    if (array_key_exists("commentNif", $data) && $data["commentNif"] != "") {
                        try {
                            $this->changeProjectToStatus($project, "refusal", $data);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Informations incomplète : vous devez laisser un commentaire pour indiquer les raisons du refus !");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/canceled", name="api_projects_status_put_canceled", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusCanceled(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "configuration") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    if (array_key_exists("commentNif", $data) && $data["commentNif"] != "") {
                        try {
                            $this->changeProjectToStatus($project, "canceled", $data);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Informations incomplète : vous devez laisser un commentaire pour indiquer les raisons de l'annulation !");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/in_progress", name="api_projects_status_put_in_progress", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusInProgress(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "configuration" || $project->getStatus() === "waiting_final_report") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    $error = $this->isProjectReadyToInProgress($project);
                    if (count($error) == 0) {
                        try {
                            $this->changeProjectToStatus($project, "in_progress", $data);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(403, $error[0]);
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/waiting_final_report", name="api_projects_status_put_waiting_final_report", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusWaitingFinalReport(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "in_progress") {
                if (!$project->isInNextPayment()) {
                    if ($this->usersNifAuthorizeToProject($project)) {
                        try {
                            $this->changeProjectToStatus($project, "waiting_final_report", $data);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
                }
                return $this->failReturn(403, "Vous ne pouvez pas changer le statut de ce projet car il est inscrit au prochain paiement.");
            }
            return $this->failReturn(403, "Ce projet ne peut pas être passé à ce statut !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/status/waiting_reserve", name="api_projects_status_put_waiting_reserve", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectStatusWaitingReserve(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->findOneById($id);

        if ($project) {
            if ($project->getStatus() === "waiting_final_report") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    try {
                        $this->changeProjectToStatus($project, "waiting_reserve", $data);
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
            }
            return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/refund", name="api_projects_refund_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectRefund(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        $project = $this->projectRepository->findOneById($id);

        if ($user->getType() === "association") {
            return $this->json(["code" => 404, "message" => "Vous n'avez pas les droits pour réaliser cette action."], 404, []);
        }

        if ($project) {
            if ($project->getStatus() === "waiting_final_report" && $project->getFinalReport() && $project->getFinalReport()->getStatus() === "valid") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    if (array_key_exists("refundAmount", $data) && array_key_exists("refundJustification", $data)) {
                        try {
                            $this->changeProjectToStatus($project, "waiting_refund", $data);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Montant et/ou justification du remboursement manquante(s).");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
            }
            return $this->failReturn(403, "Ce projet doit être en attente de rapport final et avoir un rapport final validé");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/refund/cancel", name="api_projects_refund_put_cancel", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectRefundCancel(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        $project = $this->projectRepository->findOneById($id);

        if ($user->getType() === "association") {
            return $this->failReturn(403, "Vous n'avez pas les droits pour changer de statut ce projet !");
        }

        if ($project) {
            $refund = $project->getRefund();
            if ($refund) {
                if ($project->getStatus() === "waiting_reserve" || $project->getStatus() === "waiting_refund" || $project->getStatus() === "finished") {
                    if ($this->usersNifAuthorizeToProject($project)) {
                        try {
                            $this->logs[] = array("type" => "project", "action" => "project_refund_cancel", "project" => $project);
                            $this->changeProjectToStatus($project, "waiting_final_report", $data);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
                }
                return $this->failReturn(403, "Ce projet doit être en attente de réserve, ou en attente de remboursement ou terminé");
            }
            return $this->failReturn(404, "Ce projet n'a pas de trop perçu.");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/refund/confirm", name="api_projects_refund_put_confirm", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectRefundConfirm(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            $refund = $project->getRefund();
            if ($refund) {
                if ($project->getStatus() === "waiting_reserve" || $project->getStatus() === "waiting_refund" || $project->getStatus() === "finished") {
                    if ($this->usersAuthorizeToProject($project)) {
                        try {
                            if ($user->getType() === "association") {
                                $this->logs[] = array("type" => "project", "action" => "project_update_refund_send", "project" => $project);
                                $refund->setDateSend($this->getDateNow());
                            } else {
                                if (array_key_exists("dateRefund", $data) && $data["dateRefund"] !== "") {
                                    $refund->setDateRefund(new \DateTime($data["dateRefund"]));
                                } else {
                                    $refund->setDateRefund($this->getDateNow());
                                }
                                $this->logs[] = array("type" => "project", "action" => "project_update_refund_confirm", "project" => $project);
                                $this->changeProjectToStatus($project, "finished", $data);
                            }
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
                }
                return $this->failReturn(403, "Ce projet doit être en attente de réserve, ou en attente de remboursement ou terminé");
            }
            return $this->failReturn(403, "Ce projet n'a pas de trop perçu.");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/initialallocated/confirm", name="api_projects_put_initialallocated_confirm", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectInitialAllocatedConfirm(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        $isSign = false;

        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            if ($user->getType() !== "association" && ($user->getIsPresident() || $this->usersNifAuthorizeToProjectOrSecretariat($project))) {
                $initialAllocated = $project->getInitialAllocated();
                if ($initialAllocated) {
                    try {
                        if ($user->getIsPresident()) {
                            if (!$initialAllocated->getDateCheck()) {
                                $initialAllocated->setDateCheck($this->getDateNow());
                                $this->logs[] = array("type" => "project", "action" => "project_update_validation_confirm", "project" => $project);
                            }
                            if (!$initialAllocated->getDateSign()) {
                                $initialAllocated->setDateSign($this->getDateNow());
                                $this->logs[] = array("type" => "project", "action" => "project_update_validation_sign", "project" => $project);
                                $isSign = true;
                            }
                        } else {
                            if (!$initialAllocated->getDateCheck()) {
                                $initialAllocated->setDateCheck($this->getDateNow());
                                $this->logs[] = array("type" => "project", "action" => "project_update_validation_confirm", "project" => $project);
                            }
                        }
                        $this->em->persist($initialAllocated);
                        $this->generateInitialAllocatedData($project, $initialAllocated);
                        if ($isSign) {
                            //$project->setWebStatus("draft");
                            $this->em->persist($project);
                            $this->sendMail(
                                    $project->getOrganization()->getRepresentative()->getEmail(),
                                    "Confirmation d'allocation",
                                    "validation",
                                    array("project" => $project, "type" => "initialAllocation"),
                                    array($initialAllocated->getFile()),
                                    ($project->getOrganization()->getRepresentative()->getEmail() !== $project->getContact()->getEmail()) ? $project->getContact()->getEmail() : null
                            );
                        }
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(403, "Ce projet n'a pas encore d'allocation initiale !");
            }
            return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/initialallocated", name="api_projects_put_initialallocated", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectInitialAllocated(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            if ($project->getStatus() === "configuration") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    if (array_key_exists("initialAmount", $data) && is_int($data["initialAmount"]) && $data["initialAmount"] >= 0 && array_key_exists("dateAllocated", $data)) {
                        try {
                            $initialAllocated = $project->getInitialAllocated();
                            if (!$initialAllocated) {
                                $initialAllocated = new AllocatedAmount();
                                $this->logs[] = array("type" => "project", "action" => "project_new_validation", "project" => $project);
                            } else {
                                $this->logs[] = array("type" => "project", "action" => "project_update_validation", "project" => $project);
                            }
                            $initialAllocated->setAmount($data["initialAmount"]);
                            $reserve = round(($data["initialAmount"] / 10), 2);
                            $reserve = ($reserve > 2500) ? 2500 : $reserve;
                            $initialAllocated->setReserve($reserve);
                            $initialAllocated->setDateAllocated(new \DateTime($data["dateAllocated"]));
                            if (array_key_exists("note", $data) && $data["note"] != "") {
                                $initialAllocated->setNote($data["note"]);
                            } else {
                                $initialAllocated->setNote(null);
                            }
                            $initialAllocated->setDateSign(null);
                            $initialAllocated->setDateCheck(null);
                            $percentageReserve = round(($reserve / $data["initialAmount"] * 100), 2);
                            $project->setPercentageReserve($percentageReserve);
                            $project->setPaymentType(null);
                            $project->setPaymentTerms(null);
                            // effacer les paiements
                            $this->generateInitialAllocatedData($project, $initialAllocated);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Informations incomplètes : date d'allocation et/ou montant");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour réaliser cette action.");
            }
            return $this->failReturn(403, "L'allocation initiale ne peut être modifiée que lors de la phase de configuration");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/api/projects/{id}/paymentconfiguration", name="api_projects_put_paymentconfiguration", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectPaymentConfiguration(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            if ($project->getStatus() === "configuration") {
                if ($this->usersNifAuthorizeToProject($project)) {
                    try {
                        if (array_key_exists("paymentType", $data) && in_array($data["paymentType"], array("invoice", "timeline"))) {
                            $terms = "";
                            $project->setPaymentType($data["paymentType"]);
                            if ($data["paymentType"] === "invoice") {
                                if (array_key_exists("totalAmount", $data) && is_int($data["totalAmount"]) && $data["totalAmount"] >= 0) {
                                    $project->setTotalAmount($data["totalAmount"]);
                                    $percent = round(($project->getInitialAllocated()->getAmount() * 100) / $project->getTotalAmount(), 2);
                                    $project->setPercentage($percent);
                                }
                                $this->removeReserve($project);
                                $project->removeAllPayments();
                                $terms = "Les fonds seront libérés sur enregistrement dans l'application des factures fournisseurs approuvées par l'association (signature, date, cachet de l'association) relatives aux postes du budget en annexes du projet, TVA comprise.\n"
                                        . "Le budget de ces postes étant fixé selon le budget transmis à € " . number_format($data["totalAmount"], 2, ',', ' ') . ", l'intervention sur le montant des factures est fixée à " . $percent . " % (ratio montant allocation/budget total TVA comprise), dans les limites de l’allocation consentie.\n"
                                        . "Les versements se font en fin de mois pour autant que les factures soient enregistrées avant le 13 du mois courant et qu'aucun reçu signé ne soit en souffrance. En cas de retard, le versement prévu est remis au mois suivant jusqu’à réception du reçu signé.\n\n"
                                        . "Une retenue d’un montant total de € " . number_format($project->getInitialAllocated()->getReserve(), 2, ',', ' ') . " sera cautionnée sur les versements mensuels de la Fondation et elle sera libérée dès validation du rapport final du projet.\n"
                                        . "La demande de ce rapport sera demandée par e-mail en fin de projet (prévu fin " . $project->getDateEnd()->format("m/Y") . "). Le rapport devra être enregistré sous les 60 jours. Si ce délai ne peut être respecté, l'association proposera par courriel le délai réalisable.\n\n"
                                        . "L'association transmettra 3 photos de qualité HD en format JPEG afin d'illustrer le projet sur le site vitrine de la Fondation NIF.";
                            } else {
                                $terms = "Les fonds seront libérés sous forme d'avances selon calendrier repris dans votre espace projet. Les dates de versement seront respectées pour autant que le rapport d'activités de la période antérieure soit déposé avant le 13 du mois de l'avance suivante et qu'aucun reçu à signer ne soit en attente.\n"
                                        . "En cas de retard, le versement prévu est remis au mois suivant jusqu’à réception du reçu signé et/ou du rapport intermédiaire.\n\n"
                                        . "Une retenue d’un montant total de € " . number_format($project->getInitialAllocated()->getReserve(), 2, ',', ' ') . " est cautionnée et sera libérée dès validation du rapport final narratif et financier du projet.\n"
                                        . "La demande du rapport final sera lancée à la date prévue de fin de projet.\n\n"
                                        . "L'association transmettra 3 photos de qualité HD en format JPEG afin d'illustrer le projet sur le site vitrine de la Fondation NIF.";
                                // générer la réserve si timeline
                                $project->addPayment($this->generateReserveEndProject($project));
                                $project->setTotalAmount(null);
                            }
                            $project->setPaymentType($data["paymentType"]);
                            $project->setPaymentTerms($terms);

                            $this->logs[] = array("type" => "project", "action" => "project_update_paymentType", "project" => $project);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } elseif (array_key_exists("paymentTerms", $data) && $data["paymentTerms"] != "") {
                            $project->setPaymentTerms($data["paymentTerms"]);
                            $this->logs[] = array("type" => "project", "action" => "project_update_paymentTerms", "project" => $project);
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } else {
                            return $this->failReturn(400, "Informations incomplètes");
                        }
                        return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour définir le type de paiement et ses modalités.");
            }
            return $this->failReturn(403, "L'allocation initiale ne peut être modifiée que lors de la phase de configuration");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/projects/validationContact/{id}", name="api_projects_validationcontact", methods={"GET"})
     */
    public function getProjectValidationContact(string $id) {
        $json = null;
        $project = $this->projectRepository->findOneBy(array("contactValidationId" => $id));
        if ($project) {
            try {
                $json = array();
                $json["project"] = array(
                    "id" => $project->getId(),
                    "name" => $project->getName(),
                    "number" => $project->getNumber()
                );
                $json["organization"] = array(
                    "id" => $project->getOrganization()->getId(),
                    "name" => $project->getOrganization()->getName(),
                    "representative" => $project->getOrganization()->getRepresentative()->getId()
                );
                $json["contact"] = array(
                    "id" => $project->getContact()->getId(),
                    "lastname" => $project->getContact()->getLastname(),
                    "firstname" => $project->getContact()->getFirstname()
                );
                $contacts = $this->userRepository->findBy(array("organization" => $project->getOrganization(), "isActive" => true), array("lastname" => "ASC"));
                $cs = array();
                foreach ($contacts as $c) {
                    $cs[] = array(
                        "id" => $c->getId(),
                        "lastname" => $c->getLastname(),
                        "firstname" => $c->getFirstname()
                    );
                }
                $json["contacts"] = $cs;

                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Aucune demande de validation de contact");
    }

    /**
     * @Route("/projects/{id}/contact/valid/{code}", name="api_projects_put_validationcontact", methods={"PUT"})
     */
    public function putProjectValidationContact(string $id, string $code, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->find($id);

        if ($project) {
            if ($code == $project->getContactValidationId()) {
                if (array_key_exists("contact", $data) && $data["contact"] && $data["contact"] != "") {
                    $contact = $this->userRepository->findOneById($data["contact"]);
                    if ($contact) {
                        try {
                            $project->setContact($contact);
                            $project->setContactValidationId(null);
                            $project->setIsContactValid(true);
                            $project->setContactValidationSend(null);
                            $this->logs[] = array(
                                "type" => "project",
                                "project" => $project,
                                "action" => "project_contact_validationfromemail",
                                "data" => array(
                                    "contact" => array(
                                        "lastname" => $contact->getLastname(),
                                        "firstname" => $contact->getFirstname(),
                                        "id" => $contact->getId()
                                    )
                                )
                            );
                            return $this->successPutProject($project, array_key_exists("returnRessource", $data));
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(404, "Contact inconnu");
                }
                return $this->failReturn(404, "Contact inconnu");
            }
            return $this->failReturn(404, "Aucune demande de validation de contact");
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

    /**
     * @Route("/projects/{id}/pdf", name="api_projects_get_pdf", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function getProjectPdf(string $id, Request $request) {
        $json = array();
        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            //if (($user->getType() === "association" && $project->getOrganization() === $user->getOrganization()) || $user->getType() !== "association") {
            $url = $this->myUtils->generateUniqueFileName();
            $fileName = $project->getNumber() . $this->getParameter('filename_extension');
            $this->pdfService->generatePDFProject($project, $fileName, $url);
            //}
            return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
        }
        return $this->json($json, 404, []);
    }

    /**
     * @Route("/api/projects/{id}/photos", name="api_projects_put_photo", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putProjectPhotoOrder(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $project = $this->projectRepository->find($id);

        if ($project) {
            try {
                $cpt = 0;
                foreach ($data["photos"] as $p) {
                    $photo = $this->photoRepository->find($p["id"]);
                    $photo->setPosition($cpt);
                    $this->em->persist($photo);
                    $this->em->flush();
                    $cpt++;
                }
                $project->setUpdateWp(true);
                $this->logs[] = array("type" => "project", "project" => $project, "action" => "project_order_photo");
                return $this->successPutProject($project, array_key_exists("returnRessource", $data));
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

}

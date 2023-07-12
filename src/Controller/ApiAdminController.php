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

class ApiAdminController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/admin/projects/{id}/tofinished", name="api_admin_project_tofinished", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function projectToFinished(string $id, Request $request) {
        $json = null;
        $data = ["manual" => true];
        $user = null;

        $project = $this->projectRepository->findOneById($id);
        $this->changeProjectToStatus($project, "finished", ["manual" => true]);

        return $this->successPutProject($project, true);
    }

    /**
     * @Route("/admin/addextension", name="api_admin_addextension")
     */
    public function addExtension() {
        $project = $this->projectRepository->findOneByNumber(2);
        if ($project) {
            $extension = new AllocatedAmount();
            $extension->setAmount("17000.00");
            $extension->setReserve("0.00");
            $extension->setDateAllocated(new \DateTime("2017-07-31"));
            $extension->setNote(null);
            $extension->setDateSign(new \DateTime("2017-07-31"));
            $extension->setDateCheck(new \DateTime("2017-07-31"));

            $extension->setProject($project);

            $this->em->persist($extension);
            $this->em->flush();

            return $this->successPutProject($project, true);
        }
    }

    /**
     * @Route("/api/admin/fusionOrganization", name="api_admin_fusionOrganization", methods={"POST"})
     */
    public function fusionOrganization(Request $request) {
        $data = json_decode($request->getContent(), true);

        $user = $this->security->getUser();
        if ($user->getIsAdmin()) {
            $toRemove = $this->organizationRepository->find($data["toRemove"]);
            $toKeep = $this->organizationRepository->find($data["toKeep"]);
            if ($toRemove && $toKeep && $toRemove->getId() != $toKeep->getId()) {

                try {
                    //file
                    $files = $this->fileRepository->findBy(array("organization" => $toRemove));
                    foreach ($files as $file) {
                        $file->setOrganization($toKeep);
                        $this->em->persist($file);
                        $this->em->flush();
                    }
                    //file obligatoire + RIB
                    if ($toRemove->getRib()) {
                        $file = $toRemove->getRib()->getFile();
                        if ($file) {
                            $file->setOrganization($toKeep);
                            $this->em->persist($file);
                            $this->em->flush();
                        }
                        $rib = $toRemove->getRib();
                        $toRemove->setRib(null);
                        $this->em->remove($rib);
                        $this->em->flush();
                    }
                    if ($toRemove->getAnnexeStatus()) {
                        $file = $toRemove->getAnnexeStatus();
                        $file->setOrganization($toKeep);
                        $this->em->persist($file);
                        $this->em->flush();
                        $toRemove->setAnnexeStatus(null);
                    }
                    if ($toRemove->getAnnexeReport()) {
                        $file = $toRemove->getAnnexeReport();
                        $file->setOrganization($toKeep);
                        $this->em->persist($file);
                        $this->em->flush();
                        $toRemove->setAnnexeReport(null);
                    }
                    if ($toRemove->getAnnexeAccount()) {
                        $file = $toRemove->getAnnexeAccount();
                        $file->setOrganization($toKeep);
                        $this->em->persist($file);
                        $this->em->flush();
                        $toRemove->setAnnexeAccount(null);
                    }
                    $this->em->persist($toRemove);
                    $this->em->flush();

                    //user
                    $users = $this->userRepository->findBy(array("organization" => $toRemove));
                    foreach ($users as $user) {
                        $user->setOrganization($toKeep);
                        $this->em->persist($user);
                        $this->em->persist($toRemove);
                        $this->em->flush();
                    }

                    //project
                    $projects = $this->projectRepository->findBy(array("organization" => $toRemove));
                    foreach ($projects as $project) {
                        $project->setOrganization($toKeep);
                        $this->em->persist($project);
                        $this->em->flush();
                    }

                    //log
                    $logs = $this->logActionRepository->findBy(array("organization" => $toRemove));
                    foreach ($logs as $log) {
                        $log->setOrganization($toKeep);
                        $this->em->persist($log);
                        $this->em->flush();
                    }

                    $toRemove->setRepresentative(null);
                    $this->em->persist($toRemove);
                    $this->em->flush();

                    $toRemove = $this->organizationRepository->find($data["toRemove"]);
                    $this->em->remove($toRemove);
                    $this->em->flush();

                    $this->logs[] = array("type" => "organization", "action" => "organization_fusion", "organization" => $toKeep);

                    return $this->successPutProject($toKeep, true);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Les 2 organizations ne sont pas trouvées");
        }
        return $this->failReturn(404, "Vous devez être administrateur !");
    }

    /**
     * @Route("/api/admin/fusionUser", name="api_admin_fusionUser", methods={"POST"})
     */
    public function fusionUser(Request $request) {
        $data = json_decode($request->getContent(), true);

        $user = $this->security->getUser();
        if ($user->getIsAdmin()) {
            $toRemove = $this->userRepository->find($data["toRemove"]);
            $toKeep = $this->userRepository->find($data["toKeep"]);
            if ($toRemove && $toKeep && $toRemove->getId() != $toKeep->getId()) {
                if ($toRemove->getOrganization()->getId() == $toKeep->getOrganization()->getId()) {

                    try {
                        //project
                        $projects = $this->projectRepository->findBy(array("contact" => $toRemove));
                        foreach ($projects as $project) {
                            $project->setContact($toKeep);
                            $this->em->persist($project);
                            $this->em->flush();
                        }

                        //message
                        $messages = $this->messageRepository->findBy(array("user" => $toRemove));
                        foreach ($messages as $message) {
                            $message->setUser($toKeep);
                            $this->em->persist($message);
                            $this->em->flush();
                        }

                        //log
                        $logs = $this->logActionRepository->findBy(array("author" => $toRemove));
                        foreach ($logs as $log) {
                            $log->setAuthor($toKeep);
                            $this->em->persist($log);
                            $this->em->flush();
                        }

                        $logs = $this->logActionRepository->findBy(array("user" => $toRemove));
                        foreach ($logs as $log) {
                            $log->setUser($toKeep);
                            $this->em->persist($log);
                            $this->em->flush();
                        }

                        $logs = $this->logApiRepository->findBy(array("user" => $toRemove));
                        foreach ($logs as $log) {
                            $log->setUser($toKeep);
                            $this->em->persist($log);
                            $this->em->flush();
                        }

                        //organization
                        if ($toRemove->getOrganization()->getRepresentative()->getId() == $toRemove->getId()) {
                            $organization = $toKeep->getOrganization();
                            $organization->setRepresentative($toKeep);
                            $this->em->persist($organization);
                            $this->em->flush();
                        }

                        $toRemove = $this->userRepository->find($data["toRemove"]);
                        $this->em->remove($toRemove);
                        $this->em->flush();

                        $this->logs[] = array("type" => "user", "action" => "user_fusion", "user" => $toKeep);

                        return $this->successPutProject($toKeep, true);
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(404, "Les 2 utilisateurs doivent appartenir à la même association");
            }
            return $this->failReturn(404, "Les 2 utilisateurs ne sont pas trouvées");
        }
        return $this->failReturn(404, "Vous devez être administrateur !");
    }

}

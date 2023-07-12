<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Entity\Message;
use App\Entity\File;
use App\Repository\PaymentRepository;
use App\Service\MPdfService;
use App\Utils\MyUtils;

class ApiMessageController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/messages/{id}", name="api_messages_get", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function getMessageByProject(string $id) {
        $json = null;
        $project = $this->projectRepository->findOneById($id);
        if ($project) {
            $user = $this->security->getUser();
            if (($user->getType() === "association" && $project->getOrganization()->getId() === $user->getOrganization()->getId()) || $user->getType() !== "association") {
                $json = json_decode($this->serializer->serialize(
                                $project,
                                'json',
                                ['groups' => array("projectmessage:read")]
                        ), true);

                if ($user->getType() === "association" && $user->getId() === $project->getContact()->getId()) {
                    $project->setMessageContactLastView($project->getLastMessage());
                    $project->setMessageContactNew(0);
                } elseif ($user->getType() !== "association" && $user->getId() === $project->getManager()->getId()) {
                    $project->setMessageContactLastView($project->getLastMessage());
                    $project->setMessageManagerNew(0);
                }
                try {
                    $this->em->flush();
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
        }
        return $this->json($json, 404, []);
    }

    /**
     * @Route("/api/messages", name="api_messages_post", methods={"POST"})
     */
    public function postMessage(Request $request) {
        $user = $this->security->getUser();
        $data = json_decode($request->getContent(), true);

        if (array_key_exists("id", $data)) {
            $project = $this->projectRepository->findOneById($data["id"]);
            if ($project) {
                if (($user->getType() === "association" && $project->getOrganization()->getId() === $user->getOrganization()->getId()) || $user->getType() !== "association") {
                    if (array_key_exists("content", $data)) {
                        $message = new Message();
                        $message->setContent($data["content"]);
                        $message->setProject($project);
                        $message->setType("message");
                        $message->setUser($user);
                        $message->setData(array("author" => array("firstname" => $user->getFirstname(), "lastname" => $user->getLastname())));
                        $message->setDate(new \DateTime());

                        if ($user->getType() === "association" && $user->getId() === $project->getContact()->getId()) {
                            $project->setMessageContactLastView($message);
                            $project->setMessageContactNew(0);
                            $project->setMessageManagerNew($project->getMessageManagerNew() + 1);
                        } elseif ($user->getType() !== "association" && $user->getId() === $project->getManager()->getId()) {
                            $project->setMessageManagerLastView($message);
                            $project->setMessageManagerNew(0);
                            $project->setMessageContactNew($project->getMessageContactNew() + 1);
                        }

                        try {
                            $this->em->persist($message);
                            $this->em->flush();
                            $this->em->refresh($project);

                            $json = json_decode($this->serializer->serialize(
                                            $project,
                                            'json',
                                            ['groups' => 'projectmessage:read']
                                    ), true);
                            $this->logs[] = array("type" => "project", "action" => "project_add_message", "project" => $project);
                            return $this->successReturn($json, 200);
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Vous devez envoyer un message");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour introduire un nouveau projet");
            }
            return $this->failReturn(404, "Veuillez préciser la discussion");
        }
        return $this->failReturn(404, "Veuillez préciser la discussion");
    }

}

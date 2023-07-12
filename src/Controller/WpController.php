<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Transfer;
use App\Repository\TransferRepository;
use App\Repository\InvoiceRepository;
use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;

class WpController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/wp/projects", name="api_wp_projects", methods={"GET"})
     */
    public function wpProjectsJson(Request $request) {
        $json = null;
        $projects = $this->projectRepository->getNumberNotNull();
        $json = json_decode($this->serializer->serialize(
                        $projects,
                        'json',
                        ['groups' => 'projectwp:read']
                ), true);
        return $this->successReturn($json);
    }

    /**
     * @Route("/wp/secteurs", name="api_wp_secteurs", methods={"GET"})
     */
    public function wpSecteursJson(Request $request) {
        $json = null;
        $secteurs = $this->secteurRepository->findAll();
        $json = json_decode($this->serializer->serialize(
                        $secteurs,
                        'json',
                        ['groups' => 'secteur:read']
                ), true);
        return $this->successReturn($json);
    }

    /**
     * @Route("/wp/photo/{id}/{wp}", name="api_wp_photo_get", methods={"GET"}, requirements={"id"="\d+", "wp"="\d+"})
     */
    public function getPhotoIdWp(int $id, int $wp) {
        $json = null;

        $photo = $this->photoRepository->find($id);
        if ($photo) {
            $photo->setWpId($wp);
            $this->em->persist($photo);
            $this->em->flush();
        }

        return $this->successReturn($json);
    }

    /**
     * @Route("/wp/secteur/{id}/{wp}", name="api_wp_secteur_get", methods={"GET"}, requirements={"id"="\d+", "wp"="\d+"})
     */
    public function getSecteurIdWp(int $id, int $wp) {
        $json = null;

        $secteur = $this->secteurRepository->find($id);
        if ($secteur) {
            $secteur->setIdWp($wp);
            $this->em->persist($secteur);
            $this->em->flush();
        }

        return $this->successReturn($json);
    }

    /**
     * @Route("/wp/updateProject/{number}", name="api_wp_updateProject", methods={"GET"}, requirements={"number"="\d+", "wp"="\d+"})
     */
    public function wpUpdateProject(int $number) {
        $json = null;
        $project = $this->projectRepository->findOneBy(array("number" => $number));
        if ($project) {
            $project->setUpdateWp(false);
            $this->em->persist($project);
            $this->em->flush();

            $json = json_decode($this->serializer->serialize(
                            $project,
                            'json',
                            ['groups' => 'projectwp:read']
                    ), true);
        }
        return $this->successReturn($json);
    }

}

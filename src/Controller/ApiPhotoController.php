<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Entity\Photo;
use App\Repository\PhotoRepository;
use App\Utils\MyUtils;

class ApiPhotoController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/photos", name="api_photos_post", methods={"POST"})
     */
    public function postPhotos(Request $request) {
        $return = array();
        $uploaded = $request->files->get('photo');

        if (!is_null($uploaded)) {
            if ($request->get("project")) {
                $project = $this->projectRepository->findOneById($request->get("project"));
                if ($project) {
                    try {
                        $photoName = $this->myUtils->generateUniqueFileName();
                        $uploaded->move(
                                $this->getParameter('uploadphoto_directory_root'),
                                $photoName . '.' . $uploaded->getClientOriginalExtension()
                        );
                        $img = getimagesize($this->getParameter('uploadphoto_directory_root') . "/" . $photoName . '.' . $uploaded->getClientOriginalExtension());
                        if ($img[0] >= 700) {
                            $photo = new Photo();
                            $photo->setSlug($photoName);
                            $photo->setExtension($uploaded->getClientOriginalExtension());
                            $photo->setPosition(1000);
                            $photo->setSelected(false);
                            $photo->setProject($project);
                            $this->em->persist($photo);
                            $this->logs[] = array("type" => "project", "action" => "project_add_photo", "project" => $project, array("name" => $project->getName()));

                            $project = $this->projectRepository->findOneById($request->get("project"));
                            $json = json_decode($this->serializer->serialize(
                                            $project,
                                            'json',
                                            ['groups' => 'projectfull:read']
                            ));
                            return $this->successReturn($json, 200);
                        } else {
                            return $this->failReturn(400, "La largeur doit être supérieure à 700px", null);
                        }
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
            }
            return $this->failReturn(404, "Aucun projet correspondant.");
        }
        return $this->failReturn(400, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("/api/photos/{id}", name="api_photo_delete", methods={"DELETE"})
     */
    public function deletePhoto(int $id, Request $request) {
        $json = null;
        $photo = $this->photoRepository->findOneById($id);

        if ($photo) {
            try {
                $name = $photo->getSlug() . "." . $photo->getExtension();
                $idWp = $photo->getWpId();
                $project = $photo->getProject();
                $project->removePhoto($photo);
                $project->setUpdateWp(true);
                $this->em->persist($project);
                $this->em->flush();

                shell_exec("wget " . $this->getParameter('site_wp') . "wp-content/mu-plugins/projectlink/removeimage.php?id=" . $idWp);
                @unlink($this->getParameter('uploadphoto_directory_root') . "/" . $name);

                $this->em->refresh($project);
                $json = json_decode($this->serializer->serialize(
                                $project,
                                'json',
                                ['groups' => array("projectfull:read", "secteur:read")]
                ));
                $this->logs[] = array("type" => "project", "action" => "project_remove_photo", "project" => $project);
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("/api/photos/{id}", name="api_projects_put_photoselected", methods={"PUT"})
     */
    public function putProjectPhotoSelect(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $photo = $this->photoRepository->find($id);

        if ($photo) {
            $cpt = 0;
            $photo->setSelected(!$photo->getSelected());
            $project = $photo->getProject();
            $project->setUpdateWp(true);
            $this->em->persist($project);
            $this->em->persist($photo);
            $this->logs[] = array("type" => "project", "action" => "project_selection_photo", "project" => $project);
            $this->em->flush();
            return $this->successPutProject($photo->getProject(), array_key_exists("returnRessource", $data));
        }
        return $this->failReturn(404, "Aucun projet correspondant");
    }

}

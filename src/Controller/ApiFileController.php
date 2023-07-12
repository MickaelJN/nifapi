<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\UserRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ProjectRepository;
use App\Entity\File;
use App\Repository\FileRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use App\Utils\MyUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use App\Service\MPdfService;

class ApiFileController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/files", name="api_files_post", methods={"POST"})
     */
    public function postFiles(Request $request) {
        $return = array();
        $uploaded = $request->files->get('file');
        $slug = "file";
        $user = $this->security->getUser();

        if (!is_null($uploaded)) {
            try {
                $fileName = $this->myUtils->generateUniqueFileName() . '.' . $uploaded->getClientOriginalExtension();
                $uploaded->move(
                        $this->getParameter('uploadfile_directory_root'),
                        $fileName
                );
                $file = new File();
                $file->setName($uploaded->getClientOriginalName());
                $file->setUrl($fileName);
                $file->setExtension($uploaded->getClientOriginalExtension());
                if ($request->get("description")) {
                    $file->setDescription($request->get("description"));
                }
                //$file->setTypemine($uploaded->getMimeType());

                if ($request->get("type")) {
                    $file->setType($request->get("type"));
                    if ($request->get("type") == "statuts") {
                        $date = $this->getDateNow();
                        $file->setName("Statuts et nominations");
                        $slug = "annexe_statuts";
                        if ($request->get("organization")) {
                            $organization = $this->organizationRepository->findOneById($request->get("organization"));
                            if ($organization) {
                                $oldDocument = $organization->getAnnexeStatus();
                                if ($oldDocument) {
                                    $oldDocument->setOrganization($organization);
                                    $this->em->persist($oldDocument);
                                }
                                $slug = $slug . "-" . $this->myUtils->slugify($organization->getName());
                                $organization->setAnnexeStatus($file);
                                $this->em->persist($organization);
                                $this->logs[] = array("type" => "organization", "action" => "organization_send_statuts", "organization" => $organization);
                            }
                        }
                    } elseif ($request->get("type") == "rapportannuel") {
                        $date = $this->getDateNow();
                        $file->setName("Rapport annuel");
                        $slug = "annexe_rapport-annuel";
                        if ($request->get("organization")) {
                            $organization = $this->organizationRepository->findOneById($request->get("organization"));
                            if ($organization) {
                                $oldDocument = $organization->getAnnexeReport();
                                if ($oldDocument) {
                                    $oldDocument->setOrganization($organization);
                                    $this->em->persist($oldDocument);
                                }
                                $slug = $slug . "-" . $this->myUtils->slugify($organization->getName());
                                $organization->setAnnexeReport($file);
                                $this->em->persist($organization);
                                $this->logs[] = array("type" => "organization", "action" => "organization_send_rappportannuel", "organization" => $organization);
                            }
                        }
                    } elseif ($request->get("type") == "comptabilite") {
                        $date = $this->getDateNow();
                        $file->setName("Comptes annuels");
                        $slug = "annexe_comptes-annuel";
                        if ($request->get("organization")) {
                            $organization = $this->organizationRepository->findOneById($request->get("organization"));
                            if ($organization) {
                                $oldDocument = $organization->getAnnexeAccount();
                                if ($oldDocument) {
                                    $oldDocument->setOrganization($organization);
                                    $this->em->persist($oldDocument);
                                }
                                $slug = $slug . "-" . $this->myUtils->slugify($organization->getName());
                                $organization->setAnnexeAccount($file);
                                $this->em->persist($organization);
                                $this->logs[] = array("type" => "organization", "action" => "organization_send_comptabilite", "organization" => $organization);
                            }
                        }
                    } elseif ($request->get("type") == "identity") {
                        $date = $this->getDateNow();
                        $slug = "identity";
                        if ($request->get("user")){
                            $userc = $this->userRepository->findOneById($request->get("user"));
                            if ($userc) {
                                $file->setName("justificatif " . $userc->getLastname() . " " . $userc->getFirstname());
                                $slug = $slug . "-" . $this->myUtils->slugify($userc->getLastname() . " " . $userc->getFirstname());
                                $userc->setIdentityCard($file);
                                $userc->setIdentityCardValid($user->getType() === "administrateur");
                                $this->em->persist($userc);
                                $this->logs[] = array("type" => "user", "action" => "user_update_identity", "user" => $userc);
                                if($user->getType() === "administrateur"){
                                    $this->logs[] = array("type" => "user", "action" => "user_valid_identity", "user" => $user);
                                }
                            }
                        }
                    } elseif ($request->get("type") == "sign") {
                        $date = $this->getDateNow();
                        $slug = "signature";
                        if ($request->get("user")) {
                            $userc = $this->userRepository->findOneById($request->get("user"));
                            if ($userc) {
                                $file->setName("signature " . $userc->getLastname() . " " . $userc->getFirstname());
                                $slug = $slug . "-" . $this->myUtils->slugify($userc->getLastname() . " " . $userc->getFirstname());
                                $userc->setSign($file);
                                $this->em->persist($userc);
                                $this->logs[] = array("type" => "user", "action" => "user_update_sign", "user" => $userc);
                            }
                        }
                    } elseif ($request->get("type") == "annexe") {
                        $slug = "annexe";
                        if ($request->get("project")) {
                            $project = $this->projectRepository->findOneById($request->get("project"));
                            if ($project) {
                                $slug = $slug . "-" . $this->myUtils->slugify($request->get("name"));
                                $project->addFile($file);
                                $file->setName($request->get("name"));
                                $this->em->persist($project);
                                $this->logs[] = array("type" => "project", "action" => "project_add_annexe", "project" => $project, array("name" => $file->getName()));
                            }
                        }
                    } elseif ($request->get("type") == "rgpd" || $request->get("type") == "conditions") {
                        $slug = $request->get("type");
                        $file->setName($request->get("type"));
                        $file->setType($request->get("type"));
                    }
                    $file->setSlug($slug);
                }

                $this->em->persist($file);
                $this->em->flush();

                $json = json_decode($this->serializer->serialize(
                                $file,
                                'json',
                                ['groups' => 'file:read']
                ));
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(400, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("/api/files/{id}", name="api_file_delete", methods={"DELETE"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function deleteFile(string $id) {
        $json = null;
        $file = $this->fileRepository->findOneById($id);

        if ($file) {
            try {
                $project = $file->getProject();
                $organization = $file->getOrganization();
                if ($project) {
                    $url = $file->getUrl();
                    $project->removeFile($file);
                    $this->em->persist($project);
                    $this->em->flush();

                    $json = json_decode($this->serializer->serialize(
                                    $project,
                                    'json',
                                    ['groups' => array("projectfull:read", "secteur:read")]
                    ));
                    $this->logs[] = array("type" => "project", "action" => "project_remove_annexe", "project" => $project);
                }elseif ($organization) {
                    $url = $file->getUrl();
                    $organization->removeOldFile($file);
                    $this->em->persist($organization);
                    $this->em->flush();
                    
                    $json = json_decode($this->serializer->serialize(
                                    $organization,
                                    'json',
                                    ['groups' => array("organizationfull:read")]
                    ));
                    $this->logs[] = array("type" => "organization", "action" => "organization_remove_annexe", "organization" => $organization);
                }else{
                    return $this->failReturn(404, "Erreur lors de la suppression");
                }
                @unlink($this->getParameter('uploadfile_directory_root') . "/" . $url);
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de la suppression", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Erreur lors de la suppression");
    }

    /**
     * @Route("/files/dl/{name}", name="api_files_dl")
     */
    public function downloadFile(string $name) {
        return new BinaryFileResponse($this->getParameter('uploadfile_directory_root') . '/' . $name);
    }

    /**
     * @Route("/files/display/conditions", name="api_files_conditions", methods={"GET"})
     */
    public function displayConditions(Request $request) {
        $file = $this->fileRepository->findOneBy(array("slug" => "conditions"), array("createdAt" => "DESC"));
        $filesystem = new Filesystem();
        $from = $this->getParameter('uploadfile_directory_root') . "/" . $file->getUrl();
        $newName = $file->getSlug() . "_v" . $file->getCreatedAt()->format("Y-m-d-H-i-s") . "." . $file->getExtension();
        $to = $this->getParameter('uploadfile_directory_root') . "/temp/" . $newName;
        if ($file->getExtension() !== "pdf") {
            $filesystem->copy($from, $to, true);
        } else {
            try {
                $this->pdfService->copyPdf($file, $from, $to);
            } catch (\Exception $e) {
                $filesystem->copy($from, $to, true);
            }
        }
        return $this->file($to, $file->getSlug() . "." . $file->getExtension(), ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/files/display/rgpd", name="api_files_rgpd", methods={"GET"})
     */
    public function displayRgpd(Request $request) {
        $file = $this->fileRepository->findOneBy(array("slug" => "rgpd"), array("createdAt" => "DESC"));
        $filesystem = new Filesystem();
        $from = $this->getParameter('uploadfile_directory_root') . "/" . $file->getUrl();
        $newName = $file->getSlug() . "_v" . $file->getCreatedAt()->format("Y-m-d-H-i-s") . "." . $file->getExtension();
        $to = $this->getParameter('uploadfile_directory_root') . "/temp/" . $newName;
        if ($file->getExtension() !== "pdf") {
            $filesystem->copy($from, $to, true);
        } else {
            try {
                $this->pdfService->copyPdf($file, $from, $to);
            } catch (\Exception $e) {
                $filesystem->copy($from, $to, true);
            }
        }
        return $this->file($to, $file->getSlug() . "." . $file->getExtension(), ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @Route("/files/display/{id}", name="api_files_display", methods={"GET"})
     */
    public function displayFile(string $id, Request $request) {
        $file = $this->fileRepository->find($id);
        $filesystem = new Filesystem();
        $from = $this->getParameter('uploadfile_directory_root') . "/" . $file->getUrl();
        if ($filesystem->exists($from)) {
            $newName = $file->getSlug() . "_v" . $file->getCreatedAt()->format("Y-m-d-H-i-s") . "." . $file->getExtension();
            $to = $this->getParameter('uploadfile_directory_root') . "/temp/" . $newName;
            if ($file->getExtension() !== "pdf") {
                $filesystem->copy($from, $to, true);
            } else {
                try {
                    $this->pdfService->copyPdf($file, $from, $to);
                } catch (\Exception $e) {
                    $filesystem->copy($from, $to, true);
                }
            }
            //return $this->redirect($request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/uploads/files/temp/" . $newName . "?v=" . time(), 301);
            //return $this->file($request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath()."/uploads/files/temp/".$newName."?v=".time());
            //return new BinaryFileResponse($this->getParameter('uploadfile_directory_root') . '/temp/' . $name);
            return $this->file($to, $file->getSlug() . "." . $file->getExtension(), ResponseHeaderBag::DISPOSITION_INLINE);
        }
        return $this->redirect('https://api.fondation-nif.com/files/display/' . $id);
    }

    /**
     * @Route("/api/files/document/{type}", name="api_files_document", methods={"GET"})
     */
    public function getDocument(string $type, Request $request) {
        if ($type == "rgpd" || $type == "conditions") {
            $file = $this->fileRepository->findOneBy(array("slug" => $type), array("createdAt" => "DESC"));
            $json = json_decode($this->serializer->serialize(
                            $file,
                            'json',
                            ['groups' => array("file:read")]
            ));
            return $this->successReturn($json, 200);
        }
        return $this->failReturn(403, "Ce type de fichier ne peut pas être demandé");
    }

}

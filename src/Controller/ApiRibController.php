<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\File;
use App\Entity\Rib;
use App\Utils\MyUtils;

class ApiRibController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/rib", name="api_rib_post", methods={"POST"})
     */
    public function postRib(Request $request) {
        $user = $this->security->getUser();
        $json = array();

        if ($request->get("organization") !== null) {
            $organization = $this->organizationRepository->find($request->get("organization"));
            if ($organization) {
                if ($this->usersAuthorizeToOrganization($organization)) { //ICI on pourrait améliorer avec uniquement les admins qui ont cette association comme suivi d'un projet
                    $rib = $organization->getRib();
                    if (!$rib) {
                        $rib = new Rib();
                    }
                    $this->logs[] = array("type" => "organization", "action" => "organization_send_rib", "organization" => $organization);
                    if ($request->get("iban") !== null && $request->get("bic") !== null) {
                        if (is_numeric(mb_substr($request->get("iban"), 0, 1)) || is_numeric(mb_substr($request->get("iban"), 1, 1))) {
                            return $this->failReturn(400, "Un RIB valide commence par des lettres.");
                        }
                        $country = null;
                        $code = mb_substr($request->get("iban"), 0, 2);
                        $country = $this->countryRepository->findOneByIsocode2($code);
                        if (!$country || ($country && !$country->getIsSepa())) {
                            return $this->failReturn(400, "Seulement les RIB provenant d'un pays en zone SEPA sont autorisés.");
                        }

                        if (is_numeric(mb_substr($request->get("iban"), 0, 1)) || is_numeric(mb_substr($request->get("iban"), 1, 1))) {
                            return $this->failReturn(400, "Un RIB valide commence par des lettres.");
                        }

                        if ($user->getType() !== "association") {
                            $rib->setIban($request->get("iban"));
                            $rib->setBic($request->get("bic"));
                            if ($request->get("isSepa") === true && (!$request->get("bank") || !$request->get("address"))) {
                                return $this->failReturn(400, "Données incomplètes");
                            }
                            $rib->setCountry($country);
                            $rib->setIsSepa($country->getIsSepa());
                            //$rib->setBank($request->get("bank"));
                            //$rib->setAddress($request->get("address"));
                            $rib->setIsValid(true);
                            $this->logs[] = array("type" => "organization", "action" => "organization_valid_rib", "organization" => $organization);
                        }
                        if ($user->getType() === "association" && !$request->files->get('file')) {
                            return $this->failReturn(400, "Votre RIB au format PDF est manquant");
                        }
                        $uploaded = $request->files->get('file');
                        if (!is_null($uploaded)) {
                            $fileName = "RIB-" . $this->myUtils->generateUniqueFileName() . '.' . $uploaded->getClientOriginalExtension();
                            $uploaded->move(
                                    $this->getParameter('uploadfile_directory_root'),
                                    $fileName
                            );

                            $file = new File();
                            $file->setName("RIB " . $organization->getName());
                            $file->setUrl($fileName);
                            $file->setExtension($uploaded->getClientOriginalExtension());
                            $file->setType("RIB");
                            if ($user->getType() !== "association") {
                                $oldFile = $rib->getFile();
                                if ($oldFile) {
                                    $oldFile->setOrganization($organization);
                                    $this->em->persist($oldFile);
                                }
                                $rib->setFile($file);
                            }
                            $slug = "rib_" . $this->myUtils->slugify($organization->getName());
                            $file->setSlug($slug);
                            $this->em->persist($file);
                            $this->em->flush();
                        }
                        if ($user->getType() === "association") {
                            $newRib = array();
                            $newRib["iban"] = $request->get("iban");
                            $newRib["bic"] = $request->get("bic");
                            $code = mb_substr($request->get("iban"), 0, 2);
                            $country = $this->countryRepository->findOneByIsocode2($code);
                            if ($country) {
                                $newRib["country"] = $country->getIsocode2();
                                $newRib["isSepa"] = $country->getIsSepa();
                            }
                            //$newRib["bank"] = $request->get("bank");
                            //$newRib["address"] = $request->get("address");
                            $newRib["file"] = $file->getId();
                            $rib->setNewRib($newRib);
                            $rib->setIsValid(false);
                        }
                        try {
                            $this->em->persist($rib);
                            $organization->setRib($rib);
                            $this->em->flush();
                            return $this->successReturn($json, 200);
                        } catch (\Exception $e) {
                            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                        }
                    }
                    return $this->failReturn(400, "Données incomplètes");
                }
                return $this->failReturn(403, "Vous n'avez pas les droits pour modifier ce RIB");
            }
            return $this->failReturn(404, "Association non existante");
        }
        return $this->failReturn(400, "Vous devez préciser une association");
    }

    /**
     * @Route("/api/rib/{id}", name="api_rib_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putRib(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        $json = [];

        if ($user->getType() !== "association") {
            $rib = $this->ribRepository->findOneById($id);
            if ($rib) {
                $oldRibUrl = null;
                if ($rib->getFile()) {
                    $oldRibUrl = $rib->getFile()->getUrl();
                }
                $organization = $this->organizationRepository->findOneBy(array("rib" => $rib));
                $rib->setIban($rib->getNewRib()["iban"]);
                $rib->setBic($rib->getNewRib()["bic"]);
                $code = mb_substr($rib->getNewRib()["iban"], 0, 2);
                $country = $this->countryRepository->findOneByIsocode2($code);
                if ($country) {
                    $rib->setIsSepa($country->getIsSepa());
                    $rib->setCountry($country);
                }
                //$rib->setBank($rib->getNewRib()["bank"]);
                //$rib->setAddress($rib->getNewRib()["address"]);

                $oldFile = $rib->getFile();
                if ($oldFile) {
                    $oldFile->setOrganization($organization);
                }

                $file = $this->fileRepository->find($rib->getNewRib()["file"]);
                $rib->setFile($file);
                $rib->setNewRib(null);
                $rib->setIsValid(true);
                try {
                    $this->em->persist($rib);
                    if ($oldFile) {
                        $this->em->persist($oldFile);
                    }
                    $this->em->flush();

                    /* if ($oldRibUrl) {
                      @unlink($this->getParameter('uploadfile_directory_root') . "/" . $oldRibUrl);
                      } */

                    $this->logs[] = array("type" => "organization", "action" => "organization_valid_rib", "organization" => $organization);
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "RIB non existant");
        }
        return $this->failReturn(403, "Vous n'avez pas les droits pour modifier ce RIB");
    }

}

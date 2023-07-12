<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\ProjectRepository;
use App\Repository\CountryRepository;
use App\Entity\OrganizationLocal;
use App\Repository\OrganizationLocalRepository;

class ApiOrganizationLocalController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/organizationlocals", name="api_organizationlocals_post", methods={"POST"})
     */
    public function postOrganizationLocal(Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);

        if (array_key_exists("project", $data)) {
            $project = $this->projectRepository->findOneById($data["project"]);
            if ($project) {

                $organization = new OrganizationLocal();
                $organization->setProject($project);

                if (array_key_exists("name", $data)) {
                    $organization->setName($data["name"]);
                }

                if (array_key_exists("legalStatus", $data)) {
                    $organization->setLegalStatus($data["legalStatus"]);
                }

                if (array_key_exists("acronym", $data)) {
                    $organization->setAcronym($data["acronym"]);
                }

                if (array_key_exists("identificationNumber", $data)) {
                    $organization->setIdentificationNumber($data["identificationNumber"]);
                }

                if (array_key_exists("dateOfEstablishment", $data) && is_string($data["dateOfEstablishment"])) {
                    $organization->setDateOfEstablishment(new \DateTime($data["dateOfEstablishment"]));
                }

                if (array_key_exists("dateOfPublication", $data) && is_string($data["dateOfPublication"])) {
                    $organization->setDateOfPublication(new \DateTime($data["dateOfPublication"]));
                }

                if (array_key_exists("lastname", $data)) {
                    $organization->setLastname($data["lastname"]);
                }
                if (array_key_exists("firstname", $data)) {
                    $organization->setFirstname($data["firstname"]);
                }
                if (array_key_exists("position", $data)) {
                    $organization->setPosition($data["position"]);
                }

                if (array_key_exists("headquarterAddress", $data)) {
                    $organization->setHeadquarterAddress($data["headquarterAddress"]);
                }

                if (array_key_exists("headquarterZipcode", $data)) {
                    $organization->setHeadquarterZipcode($data["headquarterZipcode"]);
                }

                if (array_key_exists("headquarterCity", $data)) {
                    $organization->setHeadquarterCity($data["headquarterCity"]);
                }

                if (array_key_exists("headquarterPostalbox", $data)) {
                    $organization->setHeadquarterPostalbox($data["headquarterPostalbox"]);
                }

                if (array_key_exists("headquarterCountry", $data)) {
                    $country = $this->countryRepository->findOneByIsocode2($data["headquarterCountry"]);
                    if ($country !== null) {
                        $organization->setHeadquarterCountry($country);
                    }
                }

                if (array_key_exists("website", $data)) {
                    $organization->setWebsite($data["website"]);
                }

                try {
                    $this->em->persist($organization);
                    $this->em->flush();

                    $json = json_decode($this->serializer->serialize(
                                    $organization,
                                    'json',
                                    ['groups' => 'organizationLocalfull:read']
                    ));
                    $this->logs[] = array("type" => "project", "action" => "project_add_local", "project" => $project, array("name" => $organization->getName()));
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(400, "Erreur lors de l'enregistrement : données manquantes");
        }
        return $this->failReturn(400, "Erreur lors de l'enregistrement : données manquantes");
    }

    /**
     * @Route("/api/organizationlocals/{id}", name="api_organizationlocal_delete", methods={"DELETE"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function deleteOrganizationLocal(string $id) {
        $json = null;
        $organization = $this->organizationLocalRepository->findOneById($id);
        if ($organization) {
            $name = $organization->getName();
            $project = $organization->getProject();
            $project->removeLocal($organization);
            try {
                $this->em->persist($project);
                $this->em->flush();

                $json = json_decode($this->serializer->serialize(
                                $project,
                                'json',
                                ['groups' => array("projectfull:read", "secteur:read")]
                ));
                $this->logs[] = array("type" => "project", "action" => "project_remove_local", "project" => $project, array("name" => $name));
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        } else {
            return $this->json($json, 404, []);
        }
        return $this->json($json, 401, []);
    }

    /**
     * @Route("/api/organizationlocals/{id}", name="api_organizationlocals_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putOrganizationLocal(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);

        $organization = $this->organizationLocalRepository->find($id);
        if ($organization) {

            if (array_key_exists("name", $data)) {
                $organization->setName($data["name"]);
            }

            if (array_key_exists("legalStatus", $data)) {
                $organization->setLegalStatus($data["legalStatus"]);
            }

            if (array_key_exists("acronym", $data)) {
                $organization->setAcronym($data["acronym"]);
            }

            if (array_key_exists("identificationNumber", $data)) {
                $organization->setIdentificationNumber($data["identificationNumber"]);
            }

            if (array_key_exists("dateOfEstablishment", $data) && is_string($data["dateOfEstablishment"])) {
                $organization->setDateOfEstablishment(new \DateTime($data["dateOfEstablishment"]));
            }

            if (array_key_exists("dateOfPublication", $data) && is_string($data["dateOfPublication"])) {
                $organization->setDateOfPublication(new \DateTime($data["dateOfPublication"]));
            }

            if (array_key_exists("lastname", $data)) {
                $organization->setLastname($data["lastname"]);
            }
            if (array_key_exists("firstname", $data)) {
                $organization->setFirstname($data["firstname"]);
            }
            if (array_key_exists("position", $data)) {
                $organization->setPosition($data["position"]);
            }

            if (array_key_exists("headquarterAddress", $data)) {
                $organization->setHeadquarterAddress($data["headquarterAddress"]);
            }

            if (array_key_exists("headquarterZipcode", $data)) {
                $organization->setHeadquarterZipcode($data["headquarterZipcode"]);
            }

            if (array_key_exists("headquarterCity", $data)) {
                $organization->setHeadquarterCity($data["headquarterCity"]);
            }

            if (array_key_exists("headquarterPostalbox", $data)) {
                $organization->setHeadquarterPostalbox($data["headquarterPostalbox"]);
            }

            if (array_key_exists("headquarterCountry", $data)) {
                $country = $this->countryRepository->findOneByIsocode2($data["headquarterCountry"]);
                if ($country !== null) {
                    $organization->setHeadquarterCountry($country);
                }
            }

            if (array_key_exists("website", $data)) {
                $organization->setWebsite($data["website"]);
            }

            try {
                $this->em->persist($organization);
                $this->em->flush();

                $json = json_decode($this->serializer->serialize(
                                $organization,
                                'json',
                                ['groups' => 'organizationLocalfull:read']
                ));
                $this->logs[] = array("type" => "project", "action" => "project_update_local", "project" => $organization->getProject(), array("name" => $organization->getName()));
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        } else {
            return $this->json("Erreur lors de l'enregistrement", 404, []);
        }
    }

}

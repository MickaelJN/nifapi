<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\OrganizationRepository;
use App\Repository\CountryRepository;
use App\Repository\UserRepository;

class ApiOrganizationController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/organizations/{id}", name="api_organizations_get_one", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function getOrganization(string $id, Request $request) {
        $json = null;
        $organization = $this->organizationRepository->findOneById($id);
        if ($organization) {
            try {
                $json = json_decode($this->serializer->serialize(
                                $organization,
                                'json',
                                ['groups' => 'organizationfull:read']
                ));
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Aucune association correspondante");
    }

    /**
     * @Route("/api/organizations", name="api_organizations_get", methods={"GET"})
     */
    public function getOrganizations(Request $request) {
        $user = $this->security->getUser();
        if ($user->getType() != "association") {
            try {
                $organizations = $this->organizationRepository->findBy([], array("name" => "ASC"));
                $json = json_decode($this->serializer->serialize(
                                $organizations,
                                'json',
                                ['groups' => 'organization:read']
                ));
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(403, "Aucune association correspondante");
    }

    /**
     * @Route("/api/organizations/{id}", name="api_organizations_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putOrganization(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();

        $organization = $this->organizationRepository->findOneById($id);
        if ($organization) {
            if ($user->getType() != "association" || $user->getOrganization()->getId() === $organization->getId()) {
                if (array_key_exists("name", $data)) {
                    $organization->setName($data["name"]);
                    $projects = $this->projectRepository->findBy(array("organization" => $organization));
                    foreach ($projects as $project) {
                        $project->setUpdateWp(true);
                        $this->em->persist($project);
                    }
                    $this->logs[] = array("type" => "organization", "action" => "organization_update_general", "organization" => $organization);
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

                if (array_key_exists("website", $data)) {
                    $organization->setWebsite($data["website"]);
                    $this->logs[] = array("type" => "organization", "action" => "organization_update_other", "organization" => $organization);
                }

                if (array_key_exists("facebook", $data)) {
                    $organization->setFacebook($data["facebook"]);
                }

                if (array_key_exists("instagram", $data)) {
                    $organization->setInstagram($data["instagram"]);
                }

                if (array_key_exists("phone", $data)) {
                    $organization->setPhone($data["phone"]);
                }

                if (array_key_exists("headquarterAddress", $data)) {
                    $organization->setHeadquarterAddress($data["headquarterAddress"]);
                    $this->logs[] = array("type" => "organization", "action" => "organization_update_headquarter", "organization" => $organization);
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

                if (array_key_exists("officeAddress", $data)) {
                    $organization->setOfficeAddress($data["officeAddress"]);
                    $this->logs[] = array("type" => "organization", "action" => "organization_update_office", "organization" => $organization);
                }

                if (array_key_exists("officeZipcode", $data)) {
                    $organization->setOfficeZipcode($data["officeZipcode"]);
                }

                if (array_key_exists("officeCity", $data)) {
                    $organization->setOfficeCity($data["officeCity"]);
                }

                if (array_key_exists("officePostalbox", $data)) {
                    $organization->setOfficePostalbox($data["officePostalbox"]);
                }

                if (array_key_exists("officeCountry", $data)) {
                    $country = $this->countryRepository->findOneByIsocode2($data["officeCountry"]);
                    if ($country !== null) {
                        $organization->setOfficeCountry($country);
                    }
                }

                if (array_key_exists("isActive", $data)) {
                    if (!$data["isActive"]) {
                        $projects = $this->projectRepository->findBy(array("organization" => $organization));
                        foreach ($projects as $p) {
                            if (!in_array($p, $this->finishedStatut)) {
                                return $this->failReturn(405, "Vous ne pouvez pas désactiver une association ayant des projets en cours. Veuillez mettre fin aux projets en cours avant.");
                            }
                        }
                    }
                    $organization->setIsActive($data["isActive"]);
                    $this->logs[] = array("type" => "organization", "action" => "organization_update_active", "organization" => $organization);
                    foreach ($organization->getContacts() as $user) {
                        if ($organization->getRepresentative() && $organization->getRepresentative()->getId() === $user->getId()) {
                            $user->setIsActive($data["isActive"]);
                        } else {
                            if (!$data["isActive"]) {
                                $user->setIsActive(false);
                            }
                        }
                        $this->em->persist($user);
                    }
                }

                $isChangeRepresentative = false;
                if (array_key_exists("representative", $data)) {
                    $representative = $this->userRepository->find($data["representative"]);
                    if ($representative !== null) {
                        $isChangeRepresentative = $organization->getRepresentative()->getId() != $representative->getId();
                        if ($organization->getRepresentative()->getId() !== $representative->getId()) {
                            $organization->setAnnexeStatus(null);
                        }
                        if (array_key_exists("gender", $data)) {
                            $representative->setGender($data["gender"]);
                        }
                        if (array_key_exists("position", $data)) {
                            $representative->setPosition($data["position"]);
                        }
                        if (array_key_exists("email", $data)) {
                            $representative->setEmail($data["email"]);
                        }
                        if (!$representative->getIsActive()) {
                            $representative->setIsActive(true);
                            $this->logs[] = array("type" => "user", "action" => "user_update_active", "user" => $representative);
                            if (!$organization->getIsActive()) {
                                $organization->setActive(true);
                                $this->logs[] = array("type" => "organization", "action" => "organization_update_active", "organization" => $organization);
                            }
                        }
                        $this->em->persist($representative);
                        $organization->setRepresentative($representative);

                        if ($isChangeRepresentative) {
                            $oldDocument = $organization->getAnnexeStatus();
                            if ($oldDocument) {
                                $oldDocument->setOrganization($organization);
                                $organization->setAnnexeStatus(null);
                                $this->em->persist($oldDocument);
                            }
                        }

                        $organization->setIsConfirm(true);
                        $this->logs[] = array("type" => "organization", "action" => "organization_update_representative", "organization" => $organization);
                    }
                }
                try {
                    $this->em->persist($organization);
                    $this->em->flush();

                    if ($isChangeRepresentative) {
                        $this->sendAllEmailContactValidationByOrganization($organization);
                    }

                    $json = json_decode($this->serializer->serialize(
                                    $organization,
                                    'json',
                                    ['groups' => 'organizationfull:read']
                    ));
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Vous n'avez pas les doits pour modifier cette association");
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement : aucune organisation correspondante");
    }

}

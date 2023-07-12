<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Organization;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Repository\CountryRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Utils\MyUtils;

class ApiSubscriptionController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/subscriptions", name="api_subscriptions_get", methods={"GET"})
     */
    public function getOrganizations(Request $request) {
        $subscriptions = $this->subscriptionRepository->findBy(array(), array("id" => "DESC"));
        $json = json_decode($this->serializer->serialize(
                        $subscriptions,
                        'json',
                        ['groups' => 'subscriptionfull:read']
        ));
        return $this->json($json, 200, []);
    }

    /**
     * @Route("/api/subscriptions/{id}", name="api_subscriptions_get_one", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function getSubscriptionById(string $id) {
        $json = null;
        $subscription = $this->subscriptionRepository->findOneById($id);
        if ($subscription) {
            $user = $this->security->getUser();
            if (($user->getType() === "association" && $subscription->getOrganization()->getUser() === $user) || $user->getType() !== "association") {
                $subscription->setAlreadyRead(true);

                try {
                    $this->em->persist($subscription);
                    $this->em->flush();
                    $json = json_decode($this->serializer->serialize(
                                    $subscription,
                                    'json',
                                    ['groups' => array("subscriptionfull:read")]
                    ));
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            } else {
                return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
            }
        }
        return $this->failReturn(404, "Inscription non trouvée");
    }

    /**
     * @Route("/subscriptions", name="api_subscription_post", methods={"POST"})
     */
    public function postSubscription(Request $request) {
        $data = json_decode($request->getContent(), true);
        $json = null;

        $emailExist = $this->userRepository->findOneBy(array("email" => $data["representativeEmail"]));
        if ($emailExist) {
            return $this->failReturn(400, "L'adresse du représentant légal est déjà utilisée !", "L'adresse email du représentant légal est déjà utilisée dans notre plateforme. Vous ne devez pas introduire le projet via ce formulaire mais en utilisant votre compte sur notre plateforme de gestion de projet. Si vous n'y avez pas accès, veuillez nous contacter à l'adresse contact@fondation-nif.com.");
        }
        if ($data["contactIsrepresentative"] == 0) {
            $emailExist = $this->userRepository->findOneBy(array("email" => $data["contactEmail"]));
            if ($emailExist) {
                return $this->failReturn(400, "L'adresse de la personne de contact est déjà utilisée !", "L'adresse email de la personne de contact est déjà utilisée dans notre plateforme. Vous ne devez pas introduire le projet via ce formulaire mais en utilisant votre compte sur notre plateforme de gestion de projet. Si vous n'y avez pas accès, veuillez nous contacter à l'adresse contact@fondation-nif.com.");
            }
        }
        $subscription = new Subscription();
        $subscription->setData($data);
        $subscription->setCreatedAt($this->getDateNow());
        $subscription->setStatus("new");
        $subscription->setAlreadyRead(false);

        try {
            $this->em->persist($subscription);
            $this->em->flush();

            $json = json_decode($this->serializer->serialize(
                            $subscription,
                            'json',
                            ['groups' => 'subscriptionfull:read']
            ));
            $this->logs[] = array("type" => "subscription", "action" => "subscription_add", array("id" => $subscription->getId(), "name" => $data["name"]));
            return $this->successReturn($json, 200);
        } catch (\Exception $e) {
            return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
        }
    }

    /**
     * @Route("api/subscriptions/{id}", name="api_subscription_put", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function putSubscription(string $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $json = null;
        $subscription = $this->subscriptionRepository->find($id);
        if ($subscription) {
            $checkIfEmailExist = $this->userRepository->findOneBy(array("email" => $data["representativeEmail"]));
            if (!$checkIfEmailExist) {
                $checkIfEmailExist = $this->userRepository->findOneBy(array("email" => $data["contactEmail"]));
                if (!$checkIfEmailExist) {
                    try {
                        $subscription->setData($data);
                        $subscription->setUpdatedAt($this->getDateNow());
                        $subscription->setStatus("new");
                        $this->em->persist($subscription);
                        $this->em->flush();

                        $json = json_decode($this->serializer->serialize(
                                        $subscription,
                                        'json',
                                        ['groups' => 'subscriptionfull:read']
                        ));
                        $this->logs[] = array("type" => "subscription", "action" => "subscription_update", "data" => array("subscription" => $subscription->getId()));

                        return $this->successReturn($json, 200);
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                } else {
                    return $this->failReturn(400, "Un compte existe déjà avec cette adresse email de contact ! Impossible de créer le compte et l'association pour le moment");
                }
            } else {
                return $this->failReturn(400, "Un compte existe déjà avec cette adresse email de représentant légal  ! Impossible de créer le compte et l'association pour le moment");
            }
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("api/subscriptions/{id}/status", name="api_subscription_put_status", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function putSubscriptionStatus(int $id, Request $request) {
        $data = json_decode($request->getContent(), true);
        $json = null;
        $contactSame = true;
        $project = null;
        $contact = null;
        $representative = null;
        //try {
        $subscription = $this->subscriptionRepository->findOneBy(array("id" => $id));
        if ($subscription && array_key_exists("newStatus", $data)) {
            if ($data["newStatus"] === "refused") {
                $subscription->setStatus($data["newStatus"]);
                $subscription->setComment($data["comment"]);
            }
            if ($data["newStatus"] === "accepted") {
                $checkIfEmailExist = $this->userRepository->findOneBy(array("email" => $subscription->getData()["representativeEmail"]));
                if (!$checkIfEmailExist) {
                    $checkIfEmailExist = $this->userRepository->findOneBy(array("email" => $subscription->getData()["contactEmail"]));
                    if (!$checkIfEmailExist) {

                        $organization = new Organization();
                        $organization->setName($subscription->getData()["name"]);
                        $organization->setLegalStatus($subscription->getData()["legalStatus"]);
                        $organization->setAcronym($subscription->getData()["acronym"]);
                        $organization->setIdentificationNumber($subscription->getData()["identificationNumber"]);
                        $organization->setWebsite($subscription->getData()["website"]);
                        $organization->setDateOfEstablishment(new \DateTime($subscription->getData()["dateOfEstablishment"]));
                        $organization->setDateOfPublication(new \DateTime($subscription->getData()["dateOfPublication"]));
                        $organization->setPhone($subscription->getData()["phone"]);
                        $organization->setHeadquarterAddress($subscription->getData()["headquarterAddress"]);
                        $organization->setHeadquarterCity($subscription->getData()["headquarterCity"]);
                        $organization->setIsConfirm(true);
                        $organization->setIsActive(true);
                        $country = $this->countryRepository->findOneByIsocode2($subscription->getData()["headquarterCountry"]);
                        if ($country !== null) {
                            $organization->setHeadquarterCountry($country);
                        }
                        //$organization->setHeadquarterPostalbox($subscription->getData()["headquarterPostalbox"]);
                        $organization->setHeadquarterZipcode($subscription->getData()["headquarterZipcode"]);
                        if (array_key_exists("officeAddress", $subscription->getData())) {
                            $organization->setOfficeAddress($subscription->getData()["officeAddress"]);
                            $organization->setOfficeCity($subscription->getData()["officeCity"]);
                            $country = $this->countryRepository->findOneByIsocode2($subscription->getData()["officeCountry"]);
                            if ($country !== null) {
                                $organization->setOfficeCountry($country);
                            }
                            // $organization->setOfficePostalbox($subscription->getData()["headquarterPostalbox"]);
                            $organization->setOfficeZipcode($subscription->getData()["officeZipcode"]);
                        } else {
                            $organization->setOfficeAddress($subscription->getData()["headquarterAddress"]);
                            $organization->setOfficeCity($subscription->getData()["headquarterCity"]);
                            $country = $this->countryRepository->findOneByIsocode2($subscription->getData()["headquarterCountry"]);
                            if ($country !== null) {
                                $organization->setOfficeCountry($country);
                            }
                            //$organization->setOfficePostalbox($subscription->getData()["headquarterPostalbox"]);
                            $organization->setOfficeZipcode($subscription->getData()["headquarterZipcode"]);
                        }
                        $this->em->persist($organization);

                        $representative = new User();
                        $representative->setGender((int) $subscription->getData()["representativeGender"]);
                        $representative->setEmail($subscription->getData()["representativeEmail"]);
                        $representative->setIsActive(true);
                        $representative->setLastname($subscription->getData()["representativeLastname"]);
                        $representative->setFirstname($subscription->getData()["representativeFirstname"]);
                        $representative->setType("association");
                        $representative->setPosition($subscription->getData()["representativePosition"]);
                        $representative->setPhone($subscription->getData()["representativePhone"]);
                        $representative->setMobile($subscription->getData()["representativeMobile"]);
                        $representative->setOrganization($organization);
                        $password = $this->userPasswordHasher->hashPassword($representative, $this->myUtils->randomPassword());
                        $representative->setVerifyCode($this->myUtils->randomPassword(64, false));
                        $representative->setVerifyCodeDate($this->getDateNow());
                        $representative->setPassword($password);
                        $representative->setPasswordValidity($this->getDateAddInterval("Y",1));
                        $representative->setIsActive(true);
                        $this->em->persist($representative);
                        $organization->setRepresentative($representative);
                        $this->em->persist($organization);

                        $contact = null;
                        if ($subscription->getData()["contactIsrepresentative"] === "0" && $subscription->getData()["representativeEmail"] !== $subscription->getData()["contactEmail"]) {
                            $contact = new User();
                            $contact->setGender((int) $subscription->getData()["contactGender"]);
                            $contact->setEmail($subscription->getData()["contactEmail"]);
                            $contact->setIsActive(true);
                            $contact->setLastname($subscription->getData()["contactLastname"]);
                            $contact->setFirstname($subscription->getData()["contactFirstname"]);
                            $contact->setType("association");
                            $contact->setPosition($subscription->getData()["contactPosition"]);
                            $contact->setPhone($subscription->getData()["contactPhone"]);
                            $contact->setMobile($subscription->getData()["contactMobile"]);
                            $contact->setOrganization($organization);
                            $password = $this->userPasswordHasher->hashPassword($contact, $this->myUtils->randomPassword());
                            $contact->setVerifyCode($this->myUtils->randomPassword(64, false));
                            $contact->setVerifyCodeDate($this->getDateNow());
                            $contact->setPassword($password);
                            $contact->setPasswordValidity($this->getDateAddInterval("Y",1));
                            $contact->setIsActive(true);
                            $this->em->persist($contact);
                            $contactSame = false;
                        }

                        if (array_key_exists("newStatus", $data) && $data["name"] != "") {
                            $project = new Project();
                            $project->setFromSubscription(true);
                            $project->setName($data["name"]);
                            $project->setOrganization($organization);
                            $project->setManager($this->userRepository->findOneBy(array("defaultManager" => true)));
                            $project->setStatus("phase_draft");
                            $project->setContact($contact ? $contact : $representative);
                            $project->setIsContactValid($contact ? false : true);
                            $project->setUpdateWp(false);
                            if (!$contact) {
                                $project->setContactValidationSend($this->getDateNow());
                                $project->setContactValidationId($this->myUtils->randomPassword(64, false));
                            }
                            $this->em->persist($project);
                        }


                        $subscription->setStatus($data["newStatus"]);
                    } else {
                        return $this->failReturn(400, "Un compte existe déjà avec cette adresse email de contact ! Impossible de créer le compte et l'association pour le moment");
                    }
                } else {
                    return $this->failReturn(400, $subscription->getData()["representativeEmail"] == "" ? "Veuillez corriger le formulaire d'inscription pour ajouter une adresse au représentant légal" : "Un compte existe déjà avec cette adresse email de représentant légal  ! Impossible de créer le compte et l'association pour le moment");
                }
            }
            $subscription->setUpdatedAt($this->getDateNow());
            try {
                $this->em->persist($subscription);
                $this->em->flush();

                $json = json_decode($this->serializer->serialize(
                                $subscription,
                                'json',
                                ['groups' => 'subscriptionfull:read']
                ));

                if ($subscription->getStatus() == "accepted") {
                    $this->logs[] = array("type" => "subscription", "action" => "subscription_accepted", "data" => array("subscription" => $subscription->getId()));
                    $this->logs[] = array("type" => "organization", "action" => "organization_add", "organization" => $organization, "data" => array("id" => $organization->getId(), "subscription" => $subscription->getId()));
                    $this->logs[] = array("type" => "project", "action" => "project_add", "project" => $project, "data" => array("id" => $project->getId(), "subscription" => $subscription->getId()));
                    $this->logs[] = array("type" => "user", "action" => "user_add", "user" => $representative, "data" => array("id" => $representative->getId(), "subscription" => $subscription->getId()));
                    $this->sendMail(
                            $representative->getEmail(),
                            $subscription->getStatus() == "accepted" ? "Demande d'inscription acceptée" : "Demande d'inscription refusée",
                            "inscription_decision",
                            array("subscription" => $subscription)
                    );
                    $this->sendMail($representative->getEmail(), "Votre compte sur la plateforme de gestion de projets de la Fondation NIF", "new_account", array("user" => $representative));
                    if ($contact) {
                        $this->logs[] = array("type" => "user", "action" => "user_add", "user" => $contact, "data" => array("id" => $contact->getId(), "subscription" => $subscription->getId()));
                        $this->sendMail(
                                $contact->getEmail(),
                                $subscription->getStatus() == "accepted" ? "Demande d'inscription acceptée" : "Demande d'inscription refusée",
                                "inscription_decision",
                                array("subscription" => $subscription)
                        );
                        $this->sendMail($contact->getEmail(), "Votre compte sur la plateforme de gestion de projets de la Fondation NIF", "new_account", array("user" => $contact));
                    }
                } else {
                    $this->logs[] = array("type" => "subscription", "action" => "subscription_refused", "data" => array("subscription" => $subscription->getId()));
                    $this->sendMail(
                            $subscription->getData()["representativeEmail"],
                            "Demande d'inscription refusée",
                            "inscription_decision",
                            array("subscription" => $subscription),
                            null,
                            ($subscription->getData()["contactIsrepresentative"] === "0" && $subscription->getData()["representativeEmail"] !== $subscription->getData()["contactEmail"]) ? $subscription->getData()["contactEmail"] : null
                    );
                }

                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(400, "Données incomplétes");
    }

}

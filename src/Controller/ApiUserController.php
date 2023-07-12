<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\OrganizationRepository;
use Doctrine\Common\Collections\Criteria;
use App\Utils\MyUtils;

class ApiUserController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/users/current", name="api_users_current_get", methods={"GET"})
     */
    public function getUsersCurrent(Request $request) {
        $user = $this->security->getUser();
        if ($user->getIsActive()) {
            $json = json_decode($this->serializer->serialize(
                            $user,
                            'json',
                            ['groups' => 'user:read']
            ));
            return $this->successReturn($json, 200);
        }
        return $this->failReturn(404, "Utilisateur non trouvé");
    }

    /**
     * @Route("/api/users", name="api_users_post", methods={"POST"})
     */
    public function userPost(Request $request): Response {
        $json = null;
        $userApp = $this->security->getUser();
        $data = json_decode($request->getContent(), true);
        $canSave = true;
        $user = $this->security->getUser();
        if (($data["type"] === "administrateur" && $user->getType() !== "association") || $data["type"] === "association") {
            if (array_key_exists("email", $data)) {
                if (!$this->userRepository->findOneBy(array("email" => $data["email"]))) {

                    $user = new User();
                    $user->setEmail($data["email"]);
                    $user->setIsActive(true);

                    if (array_key_exists("gender", $data)) {
                        $user->setGender($data["gender"]);
                    }

                    if (array_key_exists("lastname", $data)) {
                        $user->setLastname($data["lastname"]);
                    }

                    if (array_key_exists("firstname", $data)) {
                        $user->setFirstname($data["firstname"]);
                    }

                    if (array_key_exists("mobile", $data)) {
                        $user->setMobile($data["mobile"]);
                    }

                    if (array_key_exists("phone", $data)) {
                        $user->setPhone($data["phone"]);
                    }

                    if (array_key_exists("type", $data)) {
                        if ($data["type"] === "administrateur") {
                            if ($userApp->getType() === "administrateur" && $userApp->getIsAdmin()) {
                                $user->setType("administrateur");
                            } else {
                                return $this->failReturn(403, "Vous n'êtes pas autorisé à créer des administrateurs");
                            }
                        } elseif ($data["type"] === "association" && array_key_exists("organization", $data) && $data["organization"] !== "") {
                            $user->setType("association");
                            if (array_key_exists("position", $data)) {
                                $user->setPosition($data["position"]);
                            }
                            $organization = $this->organizationRepository->find($data["organization"]);
                            if ($organization && ($userApp->getType() === "administrateur" || ($userApp->getOrganization() && $userApp->getOrganization()->getId() === $organization->getId()))) {
                                $user->setOrganization($organization);
                            } else {
                                return $this->failReturn(400, "Vous n'êtes pas autorisé à ajouter un contact");
                            }
                        } else {
                            return $this->failReturn(400, "Vous n'êtes pas autorisé à ajouter un utilisateur car des informations sont manquantes");
                        }
                    } else {
                        return $this->failReturn(400, "Vous n'êtes pas autorisé à ajouter un utilisateur car des informations sont manquantes");
                    }

                    $user->setIsActive(true);

                    $user->setPassword(
                            $this->userPasswordHasher->hashPassword(
                                    $user,
                                    (array_key_exists("password", $data)) ? $data["password"] : "Ket712500@N",
                            )
                    );
                    $user->setPasswordValidity($this->getDateAddInterval("Y",1));

                    $user->setVerifyCode($this->myUtils->randomPassword(64, false));
                    $user->setVerifyCodeDate($this->getDateNow());

                    try {
                        $this->em->persist($user);
                        $this->em->flush();

                        $json = json_decode($this->serializer->serialize(
                                        $user,
                                        'json',
                                        ['groups' => 'user:read']
                        ));
                        $this->logs[] = array("type" => "user", "action" => "user_add_" . $data["type"], "user" => $user);
                        $this->sendMail($user->getEmail(), "Votre compte sur la plateforme de gestion de projets de la Fondation NIF", "new_account", array("user" => $user));
                        return $this->successReturn($json, 200);
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                } else {
                    return $this->failReturn(400, "Impossible de créer ce compte car cette adresse email est déjà utilisée !");
                }
            } else {
                return $this->failReturn(400, "Des informations sont manquantes");
            }
        }
        return $this->failReturn(403, "Vous n'êtes pas autorisé à créer des utilisateurs");
    }

    /**
     * @Route("/api/users", name="api_users_get", methods={"GET"})
     */
    public function getUsers(Request $request) {
        $user = $this->security->getUser();
        $json = null;
        $data = $request->query->all();

        if ($user->getType() === "association" && array_key_exists("type", $data) && $data["type"] === "administrateur") {
            return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
        }
        if ($user->getType() === "association") {
            $data["organization"] = $user->getOrganization();
        }
        if (array_key_exists("organization", $data) && $data["type"] === "organization") {
            $organization = $this->organizationRepository->find($data["organization"]);
            if ($organization) {
                $data["organization"] = $organization;
            } else {
                return $this->failReturn(400, "Impossible de charger les contacts de cette association");
            }
        }
        $users = $this->userRepository->findBy($data, array("lastname" => "ASC"));
        $json = json_decode($this->serializer->serialize(
                        $users,
                        'json',
                        ['groups' => 'user:read']
        ));
        return $this->successReturn($json, 200);

        /* if ($user->getType() !== "association") {
          $criteria = new Criteria();
          $criteria->where(Criteria::expr()->neq('type', 'association'));

          $users = $this->userRepository->matching($criteria);
          $json = json_decode($this->serializer->serialize(
          $users,
          'json',
          ['groups' => 'user:read']
          ));
          return $this->json($json, 200, []);
          } */
    }

    /**
     * @Route("/api/users/{id}", name="api_users_get_one", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function getUserdetail(string $id, Request $request) {
        $json = null;
        $user = $this->userRepository->findOneById($id);
        if ($user) {
            $json = json_decode($this->serializer->serialize(
                            $user,
                            'json',
                            ['groups' => 'user:read']
            ));
            return $this->successReturn($json, 200);
        }
        return $this->failReturn(404, "Utilisateur inconnu");
    }

    /**
     * @Route("/api/users/{id}", name="api_users_put", methods={"PUT"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function putUser(string $id, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $userApp = $this->security->getUser();

        $user = $this->userRepository->findOneById($id);
        if ($user) {

            if (array_key_exists("gender", $data)) {
                $user->setGender($data["gender"]);
                $this->logs[] = array("type" => "user", "action" => "user_update_general", "user" => $user);
            }

            if (array_key_exists("lastname", $data)) {
                $user->setLastname($data["lastname"]);
            }

            if (array_key_exists("firstname", $data)) {
                $user->setFirstname($data["firstname"]);
            }

            if (array_key_exists("mobile", $data)) {
                $user->setMobile($data["mobile"]);
            }

            if (array_key_exists("phone", $data)) {
                $user->setPhone($data["phone"]);
            }

            if (array_key_exists("isActive", $data)) {
                if($userApp->getId() == $user->getId()){
                    return $this->failReturn(405, "Vous ne pouvez pas désactiver votre propre compte.");
                }
                if ($data["isActive"] == false && $user->getType() == "association" && $user->getId() == $user->getOrganization()->getRepresentative()->getId()) {
                    return $this->failReturn(405, "Vous ne pouvez pas désactiver le compte du représentant légal. Il faut soit le remplacer par un autre utilisateur au préalable ou alors désactiver son association.");
                }
                if ($user->getType() == "association") {
                    if (!$data["isActive"]) {
                        $projects = $this->projectRepository->findBy(array("contact" => $user));
                        foreach ($projects as $p) {
                            if (!in_array($p, $this->finishedStatut)) {
                                return $this->failReturn(405, "Vous ne pouvez pas désactiver le compte d'un utilisateur qui personne de contact sur des projets en cours. Veuillez changer la personne de contact ou mettre fin au projet.");
                            }
                        }
                    }
                    $user->setIsActive($data["isActive"]);
                    $this->logs[] = array("type" => "user", "action" => "user_update_active", "user" => $user);
                    $organization = $user->getOrganization();
                    if ($data["isActive"] && $organization && $organization->getIsActive() !== true) {
                        $organization->setIsActive(true);
                        $this->em->persist($organization);
                        $this->logs[] = array("type" => "organization", "action" => "organization_update_active", "organization" => $organization);
                    }
                    if (!$data["isActive"] && $organization && $organization->getIsActive()) {
                        $all = true;
                        foreach ($organization->getContacts() as $contact) {
                            if ($contact->getId() != $user->getId() && $contact->getIsActive()) {
                                $all = false;
                            }
                        }
                        if ($all) {
                            $organization->setIsActive(false);
                            $this->em->persist($organization);
                            $this->logs[] = array("type" => "organization", "action" => "organization_update_active", "organization" => $organization);
                        }
                    }
                } else {
                    if ($data["isActive"] == false && $user->isDefaultManager()) {
                        return $this->failReturn(405, "Vous ne pouvez pas désactiver le compte du suivi par défaut. Veuillez nommer un autre utilisateur au préalable.");
                    }
                    if ($data["isActive"] == false && $user->isPresident()) {
                        return $this->failReturn(405, "Vous ne pouvez pas désactiver le compte du président. Veuillez nommer un autre utilisateur à sa place au préalable.");
                    }
                    if ($data["isActive"] == false && $user->getIsAdmin()) {
                        $admins = $this->userRepository->findBy(array("isAdmin" => true));
                        if (count($admins) <= 1) {
                            return $this->failReturn(405, "Vous ne pouvez pas désactiver le seul admin. Veuillez nommer un autre utilisateur comme administrateur au préalable.");
                        }
                    }
                    $user->setIsActive($data["isActive"]);
                }
            }

            if (array_key_exists("email", $data)) {
                $user->setEmail($data["email"]);
                $this->logs[] = array("type" => "user", "action" => "user_update_email", "user" => $user);
            }

            if (array_key_exists("isAdmin", $data) || array_key_exists("isSecretariat", $data) || array_key_exists("isSecretariatSupport", $data) || array_key_exists("isFinance", $data)) {
                if ($userApp->getIsAdmin()) {
                    if (array_key_exists("isAdmin", $data)) {
                        if ($data["isAdmin"] === false && $user->getIsAdmin()) {
                            $hasAdmin = $this->userRepository->findBy(array("isAdmin" => true, "isActive" => true));
                            if (count($hasAdmin) <= 1) {
                                return $this->failReturn(400, "Il est nécessaire d'avoir en permanence au moins un administrateur actif. Ainsi pour pouvoir supprimer les droits administrateur à un utilisateur, il est nécessaire au préalable de nommer un autre administrateur.");
                            }
                        }
                        $user->setIsAdmin($data["isAdmin"]);
                    }

                    if (array_key_exists("isSecretariat", $data)) {
                        $user->setIsSecretariat($data["isSecretariat"]);
                    }

                    if (array_key_exists("isSecretariatSupport", $data)) {
                        $user->setIsSecretariatSupport($data["isSecretariatSupport"]);
                    }

                    if (array_key_exists("isFinance", $data)) {
                        $user->setIsFinance($data["isFinance"]);
                    }

                    $this->logs[] = array("type" => "user", "action" => "user_update_roles", "user" => $user);
                } else {
                    return $this->failReturn(403, "Vous n'avez pas les droits pour modifier les rôles");
                }
            }

            if ($userApp->getType() === "administrateur" && array_key_exists("action", $data) && $data["action"] === "changepresident") {
                $president = $this->userRepository->findOneBy(array("isPresident" => true));
                if ($user->getType() === "administrateur" && $userApp->getIsAdmin() && $user->getId() !== $president->getId()) {
                    $president->setIsPresident(false);
                    $president->setIsActive(true);
                    $this->em->persist($president);
                    $this->logs[] = array("type" => "user", "action" => "user_new_president", "user" => $user);
                }
                if ($user->getType() === "administrateur") {
                    $user->setIsPresident(true);
                }
            }


            if (array_key_exists("position", $data)) {
                $user->setPosition($data["position"]);
                $this->logs[] = array("type" => "user", "action" => "user_update_position", "user" => $user);
            }

            /* if (array_key_exists("sign", $data)) {
              //$user->setSign($data["sign"]);
              $user->setSign(null);
              } */

            if (array_key_exists("newpassword", $data)) {
                if (($userApp->getType() !== "association" && $userApp->getIsAdmin()) || ($userApp->getType() === "association" && $userApp->getId() === $user->getId())) {
                    $user->setPassword(
                            $this->userPasswordHasher->hashPassword(
                                    $user,
                                    (array_key_exists("newpassword", $data)) ? $data["newpassword"] : $this->myUtils->randomPassword(),
                            )
                    );
                    $user->setPasswordValidity($this->getDateAddInterval("Y",1));
                    $this->logs[] = array("type" => "user", "action" => "user_update_password", "user" => $user);
                }
            }
            
            if (array_key_exists("identityCardValid", $data)) {
                if($userApp->getIsAdmin()){
                $user->setIdentityCardValid($data["identityCardValid"]);
                if($data["identityCardValid"]){
                    $this->logs[] = array("type" => "user", "action" => "user_valid_identity", "user" => $user);
                }else{
                    $user->setIdentityCard(null);
                    $this->logs[] = array("type" => "user", "action" => "user_refuse_identity", "user" => $user);
                }
                }else{
                    return $this->failReturn(403, "Vous n'avez pas les droits pour valider une pièce d'identité");
                }
            }


            try {
                $this->em->persist($user);

                $organization = $user->getOrganization();
                if (array_key_exists("action", $data) && $data["action"] == "confirmOrganization") {
                    // formulaire initial de validation d'une association si on conserve le même representant légal. Il faut tout de même le faire valider car pas de validation en v1
                    $organization->setIsConfirm(true);
                    $this->em->persist($organization);
                }
                $this->em->flush();

                if (array_key_exists("action", $data) && $data["action"] == "confirmOrganization") {
                    $this->sendAllEmailContactValidationByOrganization($organization);
                }

                $json = json_decode($this->serializer->serialize(
                                $user,
                                'json',
                                ['groups' => 'user:read']
                ));
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
    }

    /**
     * @Route("/users/reset-password/{code}", name="api_users_reset_password_get", methods={"GET"})
     */
    public function getUsersResetPassword(string $code) {
        $user = $this->userRepository->findOneBy(array("verifyCode" => $code));
        if ($user) {
            $json = json_decode($this->serializer->serialize(
                            $user,
                            'json',
                            ['groups' => 'user:read']
            ));
            return $this->successReturn($json, 200);
        }
        return $this->failReturn(404, "Demande expirée");
    }

    /**
     * @Route("/users/reset-password", name="api_users_reset_password_post", methods={"POST"})
     */
    public function postUsersResetPassword(Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        if (array_key_exists("email", $data)) {
            $user = $this->userRepository->findOneByEmail($data["email"]);
            if ($user) {
                try {
                    $user->setVerifyCode($this->myUtils->randomPassword(64, false));
                    $user->setVerifyCodeDate($this->getDateNow());
                    $this->em->persist($user);
                    $this->em->flush();

                    $this->sendMail(
                            $user->getEmail(),
                            "Changement de mot de passe",
                            "reset_password",
                            array("user" => $user)
                    );
                    $this->logs[] = array("type" => "user", "action" => "user_forgot_password", "user" => $user);
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Aucun compte actif n'est associé à cette addresse email");
        }
        return $this->failReturn(400, "Vous devez préciser un email valide");
    }

    /**
     * @Route("/users/reset-password/{code}", name="api_users_reset_password_put", methods={"PUT"})
     */
    public function putUsersResetPassword(string $code, Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(array("verifyCode" => $code));
        if ($user) {
            if (array_key_exists("password", $data)) {
                try {
                    $user->setPassword(
                            $this->userPasswordHasher->hashPassword($user, $data["password"])
                    );
                    $user->setPasswordValidity($this->getDateAddInterval("Y",1));
                    $user->setVerifyCode(null);
                    $user->setVerifyCodeDate(null);
                    $this->em->persist($user);
                    $this->em->flush();
                    $this->logs[] = array("type" => "user", "action" => "user_update_passwordfromemail", "user" => $user);
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(400, "Mot de passe non valide");
        }
        return $this->failReturn(404, "Aucun compte correspondant");
    }

    /**
     * @Route("/api/users/representative", name="api_users_post_representative", methods={"POST"})
     */
    public function userPostRepresentative(Request $request): Response {
        $json = [];
        $userApp = $this->security->getUser();
        $data = json_decode($request->getContent(), true);
        $canSave = true;
        $user = $this->security->getUser();
        if (array_key_exists("email", $data)) {
            if (!$this->userRepository->findOneBy(array("email" => $data["email"]))) {

                $user = new User();
                $user->setIsActive(true);
                $user->setEmail($data["email"]);

                if (array_key_exists("gender", $data)) {
                    $user->setGender($data["gender"]);
                }

                if (array_key_exists("lastname", $data)) {
                    $user->setLastname($data["lastname"]);
                }

                if (array_key_exists("firstname", $data)) {
                    $user->setFirstname($data["firstname"]);
                }

                if (array_key_exists("mobile", $data)) {
                    $user->setMobile($data["mobile"]);
                }

                if (array_key_exists("phone", $data)) {
                    $user->setPhone($data["phone"]);
                }

                $user->setType("association");

                if (array_key_exists("organization", $data) && $data["organization"] !== "") {
                    $user->setType("association");
                    if (array_key_exists("position", $data)) {
                        $user->setPosition($data["position"]);
                    }
                    $organization = $this->organizationRepository->find($data["organization"]);
                    if ($organization && ($userApp->getType() === "administrateur" || ($userApp->getOrganization() && $userApp->getOrganization()->getId() === $organization->getId()))) {
                        $user->setOrganization($organization);
                    } else {
                        return $this->failReturn(400, "Vous n'êtes pas autorisé à ajouter une personne légale pour cette association");
                    }
                } else {
                    return $this->failReturn(400, "Vous devez préciser une association");
                }

                $user->setIsActive(true);

                $user->setPassword(
                        $this->userPasswordHasher->hashPassword(
                                $user,
                                (array_key_exists("password", $data)) ? $data["password"] : $this->myUtils->randomPassword(),
                        )
                );
                $user->setPasswordValidity($this->getDateAddInterval("Y",1));

                $organization->setAnnexeStatus(null);

                try {
                    $this->em->persist($user);

                    if ($organization->getRepresentative()) {
                        $oldDocument = $organization->getAnnexeStatus();
                        if ($oldDocument) {
                            $oldDocument->setOrganization($organization);
                            $organization->setAnnexeStatus(null);
                            $this->em->persist($oldDocument);
                        }
                    }

                    $organization->setRepresentative($user);
                    $organization->setIsConfirm(true);
                    $this->em->persist($organization);
                    $this->em->flush();

                    $this->sendMail($user->getEmail(), "Votre compte sur la plateforme de gestion de projets de la Fondation NIF", "new_account", array("user" => $user));
                    $this->sendAllEmailContactValidationByOrganization($organization);

                    $this->logs[] = array("type" => "user", "action" => "user_add_association", "user" => $user);
                    $this->logs[] = array("type" => "user", "action" => "user_add_representative", "user" => $user);
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            } else {
                return $this->failReturn(400, "Impossible de créer ce compte car cette adresse email est déjà utilisée !");
            }
        }
        return $this->failReturn(400, "Des informations sont manquantes");
    }

    /**
     * @Route("/api/users/defaultManager", name="api_users_defaultManager_post", methods={"POST"})
     */
    public function postDefaultManager(Request $request) {
        $json = null;
        $data = json_decode($request->getContent(), true);
        $userApp = $this->security->getUser();

        if ($userApp->getIsAdmin()) {
            $user = $this->userRepository->findOneById($data["manager"]);
            if ($user) {
                try {
                    $current = $this->userRepository->findOneBy(array("defaultManager" => true));
                    if ($user->getId() != $current->getId()) {
                        $current->setDefaultManager(false);
                        $this->em->persist($current);
                        $user->setDefaultManager(true);
                        $this->em->persist($user);
                        $this->em->flush();

                        $this->logs[] = array("type" => "user", "action" => "user_new_default_manager", "user" => $user);
                    }
                    $users = $this->userRepository->findBy(array("type" => "administrateur", "isActive" => true), array("lastname" => "ASC"));
                    $json = json_decode($this->serializer->serialize(
                                    $users,
                                    'json',
                                    ['groups' => 'user:read']
                    ));
                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Utilisateur non connu");
        }
        return $this->failReturn(404, "Vous devez être administrateur pour modifier la personne de suivi par défaut");
    }

}

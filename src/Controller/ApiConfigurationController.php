<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Organization;
use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Entity\AppParameters;

class ApiConfigurationController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/configuration", name="app_configuration")
     */
    public function index(): Response {
        $user = $this->security->getUser();
        $configs = array();

        $countries = $this->countryRepository->findBy(array(), array("name" => "ASC"));
        $jsonCountries = json_decode($this->serializer->serialize(
                        $countries,
                        'json',
                        ['groups' => 'country:read']
        ));
        $configs["countries"] = $jsonCountries;

        $secteurs = $this->secteurRepository->findAll();
        $jsonSecteurs = json_decode($this->serializer->serialize(
                        $secteurs,
                        'json',
                        ['groups' => 'secteur:read']
        ));
        $configs["secteurs"] = $jsonSecteurs;

        return $this->successReturn($configs, 200);
    }

    /**
     * @Route("/configuration/countrys", name="configuration_get_country", methods={"GET"})
     */
    public function getCountries(): Response {
        $json = null;

        $countries = $this->countryRepository->findBy(array(), array("name" => "ASC"));
        $json = json_decode($this->serializer->serialize(
                        $countries,
                        'json',
                        ['groups' => 'country:read']
        ));

        return $this->successReturn($json, 200);
    }

    /**
     * @Route("/api/configuration/countrys/{id}", name="configuration_put_country", methods={"PUT"}, requirements={"page"="\d+"})
     */
    public function putCountry(int $id, Request $request): Response {
        $json = null;
        $data = json_decode($request->getContent(), true);

        $user = $this->security->getUser();
        if ($user->getIsAdmin()) {
            $country = $this->countryRepository->find($id);
            if ($country) {
                try {
                    $country->setName($data["name"]);
                    $country->setIsSepa($data["isSepa"] == 1 ? true : false);
                    $country->setRegion($data["region"]);
                    $this->em->persist($country);
                    $this->em->flush();

                    $countries = $this->countryRepository->findBy(array(), array("name" => "ASC"));
                    $json = json_decode($this->serializer->serialize(
                                    $countries,
                                    'json',
                                    ['groups' => 'country:read']
                    ));

                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Aucun pays correspondant");
        }
        return $this->failReturn(404, "Vous devez être administrateur pour modifier un pays");
    }

    /**
     * @Route("/configuration/secteurs", name="configuration_get_secteur", methods={"GET"})
     */
    public function getSecteurs(): Response {
        $json = null;

        $secteurs = $this->secteurRepository->findAll();
        $json = json_decode($this->serializer->serialize(
                        $secteurs,
                        'json',
                        ['groups' => 'secteur:read']
        ));

        return $this->successReturn($json, 200);
    }

    /**
     * @Route("/api/configuration/secteurs/{id}", name="configuration_put_secteur", methods={"PUT"}, requirements={"page"="\d+"})
     */
    public function putSecteur(int $id, Request $request): Response {
        $json = null;
        $data = json_decode($request->getContent(), true);

        $user = $this->security->getUser();
        if ($user->getIsAdmin()) {
            $secteur = $this->secteurRepository->find($id);
            if ($secteur) {
                try {
                    $secteur->setLibelle($data["libelle"]);
                    $this->em->persist($secteur);
                    $this->em->flush();

                    $secteurs = $this->secteurRepository->findAll();
                    $json = json_decode($this->serializer->serialize(
                                    $secteurs,
                                    'json',
                                    ['groups' => 'secteur:read']
                    ));

                    return $this->successReturn($json, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Aucun secteur correspondant");
        }
        return $this->failReturn(404, "Vous devez être administrateur pour modifier un secteur");
    }

    /**
     * @Route("/configuration/params", name="configuration_get_param", methods={"GET"})
     */
    public function getParams(): Response {
        $json = null;

        $params = $this->appParametersRepository->findAll();
        $return = array();
        foreach ($params as $param) {
            $return[$param->getName()] = $param->getData();
        }

        return $this->successReturn($return, 200);
    }

    /**
     * @Route("/api/configuration/params/email_deliberation", name="configuration_put_param_email_deliberation", methods={"PUT"})
     */
    public function putEmailDeliberation(Request $request): Response {
        $json = null;

        $user = $this->security->getUser();
        if ($user->getIsAdmin()) {
            $param = $this->appParametersRepository->findOneBy(array("name" => "email_deliberation"));
            if ($param) {
                try {
                    $users = $this->userRepository->findBy(array("type" => "administrateur", "isActive" => true));
                    foreach($users as $u){
                        $this->sendMail($u->getEmail(), "Projets à délibérer", "projects_deliberation", array("user" => $u));
                    }
                    $param->setData(array("send" => $this->getDateNow()->format("Y-m-d H:i:s")));
                    $this->em->persist($param);
                    $this->em->flush();

                    return $this->successReturn($param, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Aucun parametre correspondant");
        }
        return $this->failReturn(404, "Vous devez être administrateur pour modifier un parametre");
    }

    /**
     * @Route("/api/configuration/params/{name}", name="configuration_put_param", methods={"PUT"})
     */
    public function putParam(string $name, Request $request): Response {
        $json = null;
        $data = json_decode($request->getContent(), true);

        $user = $this->security->getUser();
        if ($user->getIsAdmin()) {
            $param = $this->appParametersRepository->findOneBy(array("name" => $name));
            if ($param) {
                try {
                    $param->setData($data);
                    $this->em->persist($param);
                    $this->em->flush();

                    return $this->successReturn($param, 200);
                } catch (\Exception $e) {
                    return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                }
            }
            return $this->failReturn(404, "Aucun parametre correspondant");
        }
        return $this->failReturn(404, "Vous devez être administrateur pour modifier un parametre");
    }

}

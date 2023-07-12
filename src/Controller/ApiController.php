<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Entity\Organization;
use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Entity\AppParameters;

class ApiController extends AbstractController {

    use ControllerTrait;
    
    /**
     * @Route("/", name="app_index")
     */
    public function index(): Response {
        return $this->render('api/index.html.twig', [
                    'controller_name' => 'ApiController',
        ]);
    }
    
    /**
     * @Route("/api", name="app_api")
     */
    public function indexApi(): Response {
        return $this->render('api/index.html.twig', [
                    'controller_name' => 'ApiController',
        ]);
    }


    /**
     * @Route("/countries", name="country_get", methods={"GET"})
     */
    public function getCountries(CountryRepository $countryRepository, EntityManagerInterface $em) {
        $response = file_get_contents("https://restcountries.com/v2/all", true);
        $data = json_decode($response, true);

        foreach ($data as $c) {
            $country = new Country();
            $country->setName($c["translations"]["fr"]);
            $country->setIsocode2($c["alpha2Code"]);
            switch ($c["region"]) {
                case "Asia":
                    $country->setRegion("Asie");
                    break;
                case "Europe":
                    $country->setRegion("Europe");
                    break;
                case "Africa":
                    $country->setRegion("Afrique");
                    break;
                case "Oceania":
                    $country->setRegion("Asie");
                    break;
                case "Americas":
                    $country->setRegion("Amerique");
                    break;
                default:
                    $country->setRegion("NC");
            }
            $em->persist($country);
            $em->flush();
        }

        $countries = $countryRepository->findAll();
        return $this->json($countries, 200, []);
    }
}

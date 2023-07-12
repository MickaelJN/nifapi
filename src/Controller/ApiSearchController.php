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

class ApiSearchController extends AbstractController {

    use ControllerTrait;
    
    /**
     * @Route("/api/search", name="app_search", methods={"POST"})
     */
    public function search(Request $request): Response {
        $userApp = $this->security->getUser();
        $data = json_decode($request->getContent(), true);
        $results = array();
                
        if(array_key_exists("type", $data) && $data["type"] != "" && array_key_exists("search", $data) && $data["search"] != ""){
            if($data["type"] == "project"){
                $projects = $this->projectRepository->search($data["search"], $userApp);
                foreach($projects as $project){
                    $results[] = array(
                        "type" => "project",
                        "name" => ($project->getNumber() ? $project->getNumber()." - ":"").$project->getName(),
                        "id" => $project->getId()
                    );
                }
                return $this->json($results, 200, []);
            }elseif($data["type"] == "organization"){
                $organizations = $this->organizationRepository->search($data["search"], $userApp);
                foreach($organizations as $organization){
                    $results[] = array(
                        "type" => "organization",
                        "name" => $organization->getName(),
                        "id" => $organization->getId()
                    );
                }
                return $this->json($results, 200, []);
            }elseif($data["type"] == "user"){
                $users = $this->userRepository->search($data["search"], $userApp);
                foreach($users as $user){
                    $results[] = array(
                        "type" => "user",
                        "name" => $user->getLastname(). " ".$user->getFirstname() . " (".$user->getEmail().")",
                        "id" => $user->getId()
                    );
                }
                return $this->json($results, 200, []);
            }else{
                return $this->failReturn(405, "Informations incomplétes");
            }
        }
        return $this->failReturn(405, "Informations incomplétes");
    }
    
    
}

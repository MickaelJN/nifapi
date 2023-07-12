<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;

class TestController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/test", name="app_test")
     */
    public function test(): Response {
        if ($this->parameterBag->get("fakedate") && $this->parameterBag->get("fakedate") == 1) {
            return $this->render('api/test.html.twig');
        }
        return $this->render('api/index.html.twig');
    }

    /**
     * @Route("/testApi", name="app_testapi")
     */
    public function testapi(): Response {

        $json = $this->prepareData();
        return $this->json($json, 200, []);
    }

    /**
     * @Route("/jump14", name="app_jump14")
     */
    public function jump14(): Response {

        $dateCourante = $this->getDateNow();
        $newDate = $dateCourante->format("Y-m") . "-14";
        $object = $this->getDateNowObject($newDate);
        $object->setData(array("date" => $newDate));

        // genere le transfert
        $newDateCourante = new \DateTime($newDate);
        $this->generateTransferByDate($newDateCourante->format("Y"), $newDateCourante->format("m"));

        return $this->json([], 200, []);
    }

    /**
     * @Route("/jump5", name="app_jump5")
     */
    public function jump5(): Response {

        $dateCourante = $this->getDateNow();
        $dateCourante->modify('last day of this month')->add(new \DateInterval('P5D'));
        $object = $this->getDateNowObject();
        $newDate = $dateCourante->format("Y-m-d") . " " . date("H:i:s");
        $object->setData(array("date" => $newDate));
        $this->em->flush();

        // genere demande de rapport auto
        // passe projet timeline en projet demande rapport si plus argent
        $this->askReportFinalIfNeed();

        return $this->json([], 200, []);
    }

    public function prepareData() {
        $dateCourante = $this->getDateNow();
        $dateReal = new \DateTime();

        $adminR = $this->userRepository->findBy(array("type" => "administrateur"));
        $admins = array();
        foreach ($adminR as $admin) {
            $a = array(
                "lastname" => $admin->getLastname(),
                "firstname" => $admin->getFirstname(),
                "email" => $admin->getEmail(),
                "isPresident" => $admin->getIsPresident(),
                "isAdmin" => $admin->getIsAdmin(),
                "isSecretariat" => $admin->getIsSecretariat(),
                "isScretariatSupport" => $admin->getIsSecretariatSupport()
            );
            $admins[] = $a;
        }

        $contactR = $this->userRepository->findBy(array("type" => "association"), array("organization" => "ASC"));
        $contacts = array();
        foreach ($contactR as $user) {
            $a = array(
                "lastname" => $user->getLastname(),
                "firstname" => $user->getFirstname(),
                "email" => $user->getEmail(),
                "isRepresentative" => $user->getOrganization()->getRepresentative()->getId() === $user->getId(),
                "organization" => $user->getOrganization()->getName()
            );
            $contacts[] = $a;
        }

        $json = array(
            'dateCourante' => $dateCourante->format("d/m/Y"),
            'dateReal' => $dateReal,
            'admins' => $admins,
            'contacts' => $contacts,
        );

        return $json;
    }

    /**
     * @Route("/desactiveContact", name="app_desactiveContact")
     */
    public function desactiveContact(): Response {
        $projects = $this->projectRepository->findAll();
        foreach ($projects as $project) {
            if ($project->getStatus() != "refusal" && $project->getStatus() != "finished") {
                if ($project->getContact()->getId() != $project->getOrganization()->getRepresentative()->getId()) {
                    $project->setIsContactValid(false);
                } else {
                    $project->setIsContactValid(true);
                }
            }
            $this->em->persist($project);
            $this->em->flush();
        }

        return $this->json([], 200, []);
    }

    /**
     * @Route("/sendtestmail", name="app_sendtestmail")
     */
    public function sendtestmail() {
        $alreadySend = array();
        $send = array();
        $p = array();
        $projects = $this->projectRepository->findAll();
        foreach ($projects as $project) {
            if ($project->getStatus() != "refusal" && $project->getStatus() != "finished") {
                $user = $project->getContact();
                if (!in_array($user->getId(), $alreadySend)) {
                    $user->setVerifyCode($this->myUtils->randomPassword(64, false));
                    $user->setVerifyCodeDate($this->getDateNow());
                    $this->em->persist($user);
                    $this->em->flush();
                    $data = array("user" => $user);
                    $this->sendMail($user->getEmail(), "Nouvelle version", "new_version", $data);
                    $alreadySend[] = $user->getEmail();
                }
            }
        }

        return $this->json($alreadySend, 200, []);
    }

}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Project;
use App\Entity\Phase;
use App\Entity\Payment;
use App\Entity\Refund;
use App\Entity\File;
use App\Entity\AllocatedAmount;

class IndexController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/pdf/secretariat", name="get_pdf_secretariat", methods={"GET"})
     */
    public function getPdfSecretariat(Request $request) {
        $json = array();
        $organizations = $this->organizationRepository->getAllOrganizationWithRealProject();
        if ($organizations) {
            //if (($user->getType() === "association" && $project->getOrganization() === $user->getOrganization()) || $user->getType() !== "association") {
            $url = $this->myUtils->generateUniqueFileName();
            $fileName = "export_secretariat";
            $this->pdfService->generatePDFSecretariat($organizations, $fileName, $url);
            //}
        }
        return $this->json($json, 404, []);
    }

    /**
     * @Route("/pdf/projects/{all}", name="get_pdf_projects", methods={"GET"})
     */
    public function getPdfProjects(string $all, Request $request) {
        set_time_limit(0);
        @ini_set("memory_limit", -1);

        $json = array();
        $projects = null;
        $fileName = "";
        if ($all == "all") {
            $projects = $this->projectRepository->getAllProjectsReal();
            $fileName = "recueil_complet";
        } else {
            $projects = $this->projectRepository->getAllProjectLastYear();
            $fileName = "recueil_".(date("Y") - 1);
        }
        if ($projects) {
            //if (($user->getType() === "association" && $project->getOrganization() === $user->getOrganization()) || $user->getType() !== "association") {
            $url = $this->myUtils->generateUniqueFileName();
            foreach ($projects as $p) {
                foreach ($p->getPhotos() as $ph) {
                    //ECHO "php bin/console liip:imagine:cache:resolve uploads/photos/" . "uploads/photos/".$ph->getSlug()).".".$ph->getExtension();
                    if ($ph->getSelected()) {
                        $filename1 = $this->getParameter('kernel.project_dir') . "/public/uploads/photos/" . $ph->getSlug() . "." . $ph->getExtension();
                        $filename2 = $this->getParameter('kernel.project_dir') . "/public/media/cache/my_thumb/uploads/photos/" . $ph->getSlug() . "." . $ph->getExtension();
                        if (file_exists($filename1) && filesize($filename1) && !file_exists($filename2)) {
                            //shell_exec("cd " . $this->getParameter('kernel.project_dir') . " && php bin/console liip:imagine:cache:resolve uploads/photos/" . $ph->getSlug() . "." . $ph->getExtension());
                        shell_exec("/opt/plesk/php/7.4/bin/php ".$this->getParameter('kernel.project_dir') . "/bin/console liip:imagine:cache:resolve uploads/photos/" . $ph->getSlug() . "." . $ph->getExtension());
                        }
                    }
                }
            }
            $this->pdfService->showPDFProjects($projects, $fileName, $url);
            //}
        }
        return $this->json($json, 404, []);
    }

    /**
     * @Route("/pdf/projects_deliberation", name="get_pdf_projects_deliberation", methods={"GET"})
     */
    public function getPdfProjectsDeliberation(Request $request) {
        set_time_limit(0);
        @ini_set("memory_limit", -1);

        $json = array();
        $projects = $this->projectRepository->findBy(array("status" => "deliberation"));
        if ($projects) {
            $url = $this->myUtils->generateUniqueFileName();
            $fileName = "projets_deliberation_" . date("Y-m-d");
            $this->pdfService->generatePDFProjectsDeliberation($projects, $fileName, $url);
        }
        return $this->json($json, 404, []);
    }

}

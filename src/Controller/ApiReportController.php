<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Entity\Report;
use App\Entity\File;
use App\Repository\ReportRepository;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\MPdfService;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;
use App\Utils\MyUtils;

class ApiReportController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/reports", name="api_report_post", methods={"POST"})
     */
    public function postReport(Request $request) {

        $user = $this->security->getUser();
        $payment = null;
        $json = null;
        $number = null;

        $payment = $this->paymentRepository->find($request->get("payment"));
        $project = $payment->getProject();

        if ($payment) {
            $report = new Report();
            if ($request->get("report")) {
                $report = $this->reportRepository->findOneById($request->get("report"));
                if (!$report) {
                    return $this->failReturn(404, "Erreur lors de l'enregistrement");
                }
            }
            $report->setRetard($request->get("retard"));
            if ($request->get("newEndDate")) {
                $report->setNewEndDate(new \DateTime($request->get("newEndDate")));
                $report->setProblems($request->get("problems"));
            }
            $report->setRetard($request->get("changeObjectif"));
            if ($request->get("changeObjectif")) {
                $report->setChangeObjectif($request->get("changeObjectif"));
                $report->setChangeObjectifDescription($request->get("changeObjectifDescription"));
            }
            $report->setTotalExpense($request->get("totalExpense"));
            $report->setStatus("new");
            $report->setComment($request->get("comment"));

            $uploaded = $request->files->get('pdf');
            if (!is_null($uploaded)) {
                $fileName = "Rapport-" . $this->myUtils->generateUniqueFileName() . '.' . $uploaded->getClientOriginalExtension();
                $uploaded->move(
                        $this->getParameter('uploadfile_directory_root'),
                        $fileName
                );
                $file = new File();
                $file->setName("Rapport " . $payment->getDatePayment()->format('Y-m'));
                $file->setUrl($fileName);
                $file->setExtension($uploaded->getClientOriginalExtension());
                $file->setType("report");
                $slug = "rapport_P" . $project->getNumber();
                $file->setSlug($slug);
                $this->em->persist($file);
                $report->setPdf($file);
            }

            $report->setIsFinal(false);

            try {
                $this->em->persist($report);
                $payment->setReport($report);
                $this->em->persist($payment);
                $this->em->flush();
                $json = json_decode($this->serializer->serialize(
                                $report,
                                'json',
                                ['groups' => array("report:read")]
                ));
                $this->logs[] = array("type" => "project", "action" => "project_add_report", "project" => $project);
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("/api/reports/final", name="api_report_postfinal", methods={"POST"})
     */
    public function postReportFinal(Request $request) {

        $user = $this->security->getUser();
        $payment = null;
        $project = null;
        $json = null;
        $number = null;

        $project = $this->projectRepository->find($request->get("project"));

        if ($project) {
            $report = new Report();
            if ($request->get("report")) {
                $report = $this->reportRepository->findOneById($request->get("report"));
                if (!$report) {
                    return $this->failReturn(404, "Erreur lors de l'enregistrement");
                }
            }

            $report->setRetard($request->get("retard"));
            if ($request->get("newEndDate")) {
                $report->setNewEndDate(new \DateTime($request->get("newEndDate")));
                $report->setProblems($request->get("problems"));
            }
            $report->setRetard($request->get("changeObjectif"));
            if ($request->get("changeObjectif")) {
                $report->setChangeObjectif($request->get("changeObjectif"));
                $report->setChangeObjectifDescription($request->get("changeObjectifDescription"));
            }
            $report->setTotalExpense($request->get("totalExpense"));
            $report->setStatus("new");
            $report->setComment($request->get("comment"));

            $uploaded = $request->files->get('pdf');
            if (!is_null($uploaded)) {
                $fileName = "Rapport-" . $this->myUtils->generateUniqueFileName() . '.' . $uploaded->getClientOriginalExtension();
                $uploaded->move(
                        $this->getParameter('uploadfile_directory_root'),
                        $fileName
                );
                $file = new File();
                if ($request->get("isFinal")) {
                    $file->setName("RapportFinal " . $project->getNumber());
                } else {
                    $file->setName("Rapport " . $project->getNumber() . " " . $payment->getDatePayment()->format('Y-m'));
                }
                $file->setUrl($fileName);
                $file->setExtension($uploaded->getClientOriginalExtension());
                $file->setType("reportfinal");
                $slug = "rapportfinal_P" . $project->getNumber();
                $file->setSlug($slug);
                $this->em->persist($file);
                $report->setPdf($file);
            }

            $report->setIsFinal(true);

            try {
                $this->em->persist($report);
                $project->setFinalReport($report);
                $this->em->persist($project);
                $this->em->flush();
                $json = json_decode($this->serializer->serialize(
                                $report,
                                'json',
                                ['groups' => array("report:read")]
                ));
                $this->logs[] = array("type" => "project", "action" => "project_add_finalreport", "project" => $project);
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("/api/reports/{id}", name="api_reports_put", methods={"PUT"}, requirements={"page"="\d+"})
     */
    public function putReport(string $id, Request $request) {
        $json = null;
        $report = $this->reportRepository->findOneById($id);

        if ($report) {
            $report->setRetard($request->get("retard"));
            if ($request->get("newEndDate")) {
                $report->setNewEndDate(new \DateTime($request->get("newEndDate")));
                $report->setProblems($request->get("problems"));
            }
            $report->setRetard($request->get("changeObjectif"));
            if ($request->get("changeObjectif")) {
                $report->setChangeObjectif($request->get("changeObjectif"));
                $report->setChangeObjectifDescription($request->get("changeObjectifDescription"));
            }
            $report->setTotalExpense($request->get("totalExpense"));
            $report->setStatus("new");
            $report->setComment($request->get("comment"));

            $uploaded = $request->files->get('pdf');
            if (!is_null($uploaded)) {
                $fileName = "Rapport-" . $this->myUtils->generateUniqueFileName() . '.' . $uploaded->getClientOriginalExtension();
                $uploaded->move(
                        $this->getParameter('uploadfile_directory_root'),
                        $fileName
                );
                $file = new File();
                $file->setName("Rapport " . $payment->getProject()->getNumber() . " " . $payment->getDatePayment()->format('Y-m'));
                $file->setUrl($fileName);
                $file->setExtension($uploaded->getClientOriginalExtension());
                $file->setType("report");
                $slug = "Rapport_P" . $project->getNumber();
                $file->setSlug($slug);
                $this->em->persist($file);
                $report->setPdf($file);
            }

            try {
                $this->em->persist($report);
                $this->em->flush();
                $json = json_decode($this->serializer->serialize(
                                $report,
                                'json',
                                ['groups' => array("report:read")]
                ));
                $this->logs[] = array("type" => "project", "action" => "project_update_report", "project" => $project);
                return $this->successReturn($json, 200);
            } catch (\Exception $e) {
                return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
            }
        }
        return $this->failReturn(404, "Erreur lors de l'enregistrement");
    }

    /**
     * @Route("/api/reports/{id}/validation", name="api_reports_put_validation", methods={"PUT"}, requirements={"page"="\d+"})
     */
    public function putReportValidation(string $id, Request $request) {
        $user = $this->security->getUser();
        $json = null;
        $data = json_decode($request->getContent(), true);

        if ($user->getType() !== "association") {
            $report = $this->reportRepository->find($id);
            if ($report) {
                if (array_key_exists("status", $data)) {
                    $report->setStatus($data["status"]);
                    if ($data["status"] === "refused") {
                        $report->setRefusDescription($data["refusDescription"]);
                    }
                    try {
                        $this->em->persist($report);
                        $this->em->flush();

                        $json = json_decode($this->serializer->serialize(
                                        $report,
                                        'json',
                                        ['groups' => array("report:read")]
                        ));

                        $project = null;
                        if ($report->getIsFinal()) {
                            $project = $this->projectRepository->findOneBy(array("finalReport" => $report));
                        } else {
                            $payment = $this->paymentRepository->findOneBy(array("report" => $report));
                            if ($payment) {
                                $project = $payment->getProject();
                            }
                        }

                        $this->logs[] = array("type" => "project", "action" => "project_" . $data["status"] . "_" . ($report->getIsFinal() ? "final" : "") . "report", "project" => $project);
                        return $this->successReturn($json, 200);
                    } catch (\Exception $e) {
                        return $this->failReturn(400, "Erreur lors de l'enregistrement", $e->getMessage());
                    }
                }
                return $this->failReturn(400, "Parametre manquant !");
            }
            return $this->failReturn(404, "Aucun rapport !");
        }
        return $this->failReturn(403, "Vous n'êtes pas autorisé à voir ce contenu");
    }

}

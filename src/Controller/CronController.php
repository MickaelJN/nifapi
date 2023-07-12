<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Transfer;
use App\Repository\TransferRepository;
use App\Repository\InvoiceRepository;
use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;

class CronController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/cron/generate_transfer", name="api_cron_generatetransfer", methods={"GET"})
     */
    public function generateTransfer(Request $request) {
        $date = $this->getDateNow();
        return $this->generateTransferByDate($date->format("Y"), $date->format("m"), $request);
    }

    /**
     * @Route("/cron/emailCheckTransfer", name="api_cron_emailCheckTransfer", methods={"GET"})
     */
    public function emailCheckTransfer() {
        $date = $this->getDateNow();
        if ($date->format("d") % 2) {
            $transfer = $this->transferRepository->findOneBy(array("status" => "waiting_execution"), array("dateExecution" => "DESC"));
            if ($transfer && $transfer->getDateExecution() < $date) {
                $admins = $this->userRepository->findBy(array("type" => "administrateur", "isActive" => true));
                foreach ($admins as $admin) {
                    if ($admin->getIsSecretariat() || $admin->getIsSecretariatSupport() || $admin->getIsFinance() || $admin->getIsAdmin()) {
                        $this->sendMail(
                                $admin->getEmail(),
                                "Vérification des versements du mois - " . $transfer->getMonth() . "/" . $transfer->getYear(),
                                "transfer_notif",
                                array("transfer" => $transfer, "admin" => $admin, "newStatus" => true),
                        );
                    }
                }
            }
        }
        return $this->json(array("success" => "ok"), 200, []);
    }

    /**
     * @Route("/cron/emailnewmessage", name="api_cron_emailnewmessage", methods={"GET"})
     */
    public function sendEmailNewMessage(Request $request) {
        $users = $this->userRepository->getUsersWithNewMessage();
        $sendEmail = array();
        foreach ($users as $user) {
            $this->sendMail($user["email"], "Nouveau message", "new_message");
            $sendEmail[] = $user["email"];
        }
        return $this->json($sendEmail, 200, []);
    }

    /**
     * @Route("/cron/askreport", name="api_cron_askreport", methods={"GET"})
     */
    public function askReport(Request $request) {

        try {
            $sendMessage = array();
            $projects = $this->projectRepository->findBy(array("status" => "in_progress", "paymentType" => "timeline"));
            foreach ($projects as $project) {
                $payments = $this->getPaymentsNeedReport($project);
                if (!empty($payments) && count($payments) > 0) {
                    $this->logs[] = array("type" => "project", "action" => "project_waiting_report", "project" => $project);
                    $sendMessage[] = $project->getNumber();
                }
            }
            return $this->successReturn($sendMessage, 200);
        } catch (\Exception $e) {
            return $this->failReturn(400, "Erreur demande rapport", $e->getMessage());
        }
        return $this->failReturn(400, "Erreur demande rapport", $e->getMessage());
    }

    /**
     * @Route("/cron/projectDateEndReached", name="api_cron_projectDateEndReached", methods={"GET"})
     */
    public function projectDateEndReached(Request $request) {

        try {
            $sendMessage = array();
            $date = $this->getDateNow();
            $projects = $this->projectRepository->findBy(array("status" => "in_progress", "paymentType" => "timeline"));
            foreach ($projects as $project) {
                if ($date > $project->getDateEnd()) {
                    $this->changeProjectToStatus($project, "waiting_final_report", array("changeAuto" => true, "cause" => "project_date_end_reached"));
                    $sendMessage[] = $project->getNumber();
                }
            }
            return $this->successReturn($sendMessage, 200);
        } catch (\Exception $e) {
            return $this->failReturn(400, "Erreur demande rapport", $e->getMessage());
        }
    }
    

    /**
     * @Route("/cron/dump_bdd", name="api_cron_dump_bdd", methods={"GET"})
     */
    public function dumpBdd(Request $request) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $connection = $this->em->getConnection()->getParams();

        $dir = $this->getParameter('backup_directory') . '/dump.sql';
        $output = "";

        echo "<h3>Backing up database to `<code>{$dir}</code>`</h3>";

        $output = shell_exec("mysqldump --user={$connection["user"]} --password={$connection["password"]} --host={$connection["host"]} --port={$connection["port"]} {$connection["dbname"]} --result-file={$dir} 2>&1");

        return $this->successReturn(array("return" => ""), 200);
    }

}

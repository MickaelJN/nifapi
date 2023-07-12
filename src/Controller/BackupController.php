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
use Google\Client;
use Google\Service;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class BackupController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/backup/bdd", name="backup_bdd", methods={"GET"})
     */
    public function dumpBdd(Request $request) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        ini_set('max_execution_time', '0');

        $id = date("YmdHis");
        $connection = $this->em->getConnection()->getParams();
        $currentdir = explode("/", $this->getParameter('kernel.project_dir'));
        $currentdir = end($currentdir);
        $filename = $currentdir . "_backup_db_" . $id;

        $file = $this->getParameter('backup_directory') . '/' . $filename . '.sql';
        $output = shell_exec("cd ".$this->getParameter('backup_directory')." &&  mysqldump --user={$connection["user"]} --password={$connection["password"]} --host={$connection["host"]} --port={$connection["port"]} --default-character-set=utf8mb4 --skip-set-charset --no-tablespaces -N --routines --skip-triggers {$connection["dbname"]} --result-file={$file} 2>&1");
        shell_exec("cd ".$this->getParameter('backup_directory')." && gzip -9 " . $filename . ".sql");

        
        $test = $this->saveToGoogleDrive($filename . ".sql.gz");
        
        return $this->successReturn(array("return" => $output), 200);
    }

    /**
     * @Route("/backup/files", name="backup_files", methods={"GET"})
     */
    public function dumpFiles(Request $request) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        ini_set('max_execution_time', '0');

        $id = date("YmdHis");
        $currentdir = explode("/", $this->getParameter('kernel.project_dir'));
        $currentdir = end($currentdir);
        $filename = $currentdir . "_backup_files_" . $id;

        $output = shell_exec("cd " . $this->getParameter('kernel.project_dir') . "/../ && tar --exclude='vendor' --exclude='var' --exclude='public/backup' -cvf " . $filename . ".tar " . $currentdir . "/");
        $output = shell_exec("cd " . $this->getParameter('kernel.project_dir') . "/../ && gzip -9 " . $filename . ".tar");
        $output = shell_exec("cd " . $this->getParameter('kernel.project_dir') . "/../ && mv " . $filename . ".tar.gz " . $this->getParameter('backup_directory'));

        $test = $this->saveToGoogleDrive($filename . ".tar.gz");

        return $this->successReturn(array("return" => $test), 200);
    }

    public function saveToGoogleDrive($filename) {

        $client = $this->getClient();

        $service = new Drive($client);

        $file = new DriveFile();
        $file = new Drive\DriveFile(array(
            'name' => $filename,
            'parents' => array("1VPhoFWMmeFZg6BPIUwibk8Ta1Ufo_BpO")
        ));
        $file->setName($filename);
        $result = $service->files->create(
                $file,
                [
                    'data' => file_get_contents($this->getParameter('backup_directory') . "/" . $filename),
                    'mimeType' => 'application/gzip',
                    'uploadType' => 'multipart'
                ]
        );

        return $result;
    }

    public function getClient() {
        $client = new Client();
        $client->setScopes(array('https://www.googleapis.com/auth/drive'));
        $client->setAuthConfig($this->getParameter('kernel.project_dir') . '/credentials.json');

        return $client;
    }

}

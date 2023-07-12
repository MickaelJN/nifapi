<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MPdfService;
use App\Repository\ProjectRepository;
use App\Entity\File;

class PdfController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/pdf/validation/{id}", name="pdf_validation", methods={"GET"}, requirements={"id"="^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$"})
     */
    public function index(string $id, MPdfService $pdf, ProjectRepository $projectRepository): Response {

        $project = $projectRepository->findOneById($id);

        $name = "NIF-validation-" . $project->getNumber();
        $template = $this->renderView('pdf/validation.html.twig', array('project' => $project));
        $header = $this->renderView('pdf/header.html.twig', array('project' => $project));
        $footer = $this->renderView('pdf/footer.html.twig', array('project' => $project));

        $pdf->create(15, 15, 15, 15, 15, 10, 10);

        return $pdf->showPdf($template, $header, $footer, $name, $this->getParameter('uploadfile_directory_root'));
    }

    /**
     * @Route("/pdf/transfer/{year}/{month}", name="pdf_transfer", methods={"GET"})
     */
    public function test(int $year, string $month, MPdfService $pdf): Response {
        $transfer = $this->transferRepository->findOneBy(array("year" => $year, "month" => $month));
        $name = "NIF_virements_" . $year . "_" . $month;
        $template = $this->renderView('pdf/transfer.html.twig', array('transfer' => $transfer));

        $pdf->create(15, 15, 15, 15, 15, 10, 10);

        return $pdf->showPdf($template, null, null, $name);
    }

    /**
     * @Route("/pdf/test", name="pdf_recu", methods={"GET"})
     */
    public function testRecu(MPdfService $pdf): Response {
        $payments = $this->paymentRepository->findByDatePayment(new \DateTime("2022-12-29"));
        foreach ($payments as $payment) {
            $url = $this->myUtils->generateUniqueFileName();
            $fileName = "NIF-recu-" . $payment->getProject()->getNumber() . "-" . $payment->getTransfer()->getYear() . "-" . $payment->getTransfer()->getMonth();
            $pdf->generatePDFRecu($payment, $fileName, $url);
            $file = new File();
            $file->setName("Validation allocation");
            $file->setUrl($url . ".pdf");
            $file->setExtension("pdf");
            $file->setType("validation");
            $file->setSlug($fileName);
            $payment->setReceipt($file);
            $payment->setDatePayment($payment->getTransfer()->getDateExecution());
            //$this->changeStatusAfterTransferValidation($payment, $em);
            $this->em->persist($payment);
        }
        $this->em->flush();
        return $this->json([], 200, []);
    }
    
    
    /**
     * @Route("/testcc", name="testcc")
     */
    public function testcc(): Response
    {
        return $this->render('index.html.twig');
    }

}

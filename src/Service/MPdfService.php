<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Mpdf\Mpdf;
use Twig\Environment;

class MPdfService {

    private $pdf;
    private $twig;
    private $container;

    public function __construct(Environment $twig, ContainerInterface $container) {
        $this->twig = $twig;
        $this->container = $container;
    }

    public function create($mt = 15, $mb = 15, $ml = 15, $mr = 15, $mh = 10, $mf = 10) {
        $this->pdf = new Mpdf([
            'margin_top' => $mt,
            'margin_bottom' => $mb,
            'margin_left' => $ml,
            'margin_right' => $mr,
            'margin_header' => $mh,
            'margin_footer' => $mf,
            'default_font_size' => 13,
            'default_font' => 'sans-serif'
        ]);
        $this->pdf->SetAuthor('Fondation Nif');
        $this->pdf->SetCreator('Fondation Nif');
    }

    public function generatePdf($template, $header, $footer, $name, $url, $firstPage = false, $toc = false, $dir_file = "uploadfile_directory_root") {
               
        $this->pdf->SetTitle($name);
        if ($firstPage) {
            $this->displayFirstPage($firstPage);
        }
        if ($toc) {
            $this->displayTocWithClick($toc);
        }
        if ($header) {
            $this->pdf->SetHTMLHeader($header);
        }
        if ($footer) {
            $this->pdf->SetHTMLFooter($footer);
        }
        $texts = explode("<!-- maxsize_hack -->",$template);
        foreach ($texts as $t) {
            $this->pdf->writeHTML($t);
        }

        header("Content-type:application/pdf");
        $this->pdf->Output($this->container->getParameter($dir_file) . "/" . $url . ".pdf", \Mpdf\Output\Destination::FILE);
    }

    public function showPdf($template, $header, $footer, $name, $firstPage = false, $toc = false, $dir_file = "uploadfile_directory_root") {
        
        $this->pdf->SetTitle($name);
        if ($firstPage) {
            $this->displayFirstPage($firstPage);
        }
        if ($toc) {
            $this->displayTocWithClick($toc);
        }
        if ($header) {
            $this->pdf->SetHTMLHeader($header);
        }
        if ($footer) {
            $this->pdf->SetHTMLFooter($footer);
        }
        $texts = explode("<!-- maxsize_hack -->",$template);
        foreach ($texts as $t) {
            $this->pdf->writeHTML($t);
        }

        header("Content-type:application/pdf");
        $this->pdf->Output($this->container->getParameter($dir_file) . "/" . $name . ".pdf", \Mpdf\Output\Destination::FILE);
        echo file_get_contents($this->container->getParameter($dir_file) . "/" . $name . '.pdf');
    }

    public function generatePDFValidation($project, $name, $url) {
        $template = $this->twig->render('pdf/validation.html.twig', array('project' => $project));
        $header = $this->twig->render('pdf/header.html.twig', array('project' => $project));
        $footer = $this->twig->render('pdf/footer.html.twig', array('project' => $project));
        $this->create();
        $this->generatePdf($template, $header, $footer, $name, $url);
    }

    public function generatePDFExtension($extension, $name, $url) {
        $template = $this->twig->render('pdf/extension.html.twig', array('extension' => $extension));
        $header = $this->twig->render('pdf/header.html.twig', array('extension' => $extension));
        $footer = $this->twig->render('pdf/footer.html.twig', array('extension' => $extension));
        $this->create();
        $this->generatePdf($template, $header, $footer, $name, $url);
    }

    public function generatePDFRecu($payment, $name, $url) {
        $template = $this->twig->render('pdf/recu.html.twig', array('payment' => $payment));
        // $header = $this->twig->render('pdf/header.html.twig');
        // $footer = $this->twig->render('pdf/footer.html.twig');
        $this->create();
        $this->generatePdf($template, null, null, $name, $url);
    }

    public function generatePDFTransfer($transfer, $name, $url) {
        $template = $this->twig->render('pdf/transfer.html.twig', array('transfer' => $transfer));
        $this->create();
        $this->generatePdf($template, null, null, $name, $url);
    }

    public function generatePDFProject($project, $name, $url) {
        $template = $this->twig->render('pdf/project.html.twig', array('project' => $project));
        $this->create();
        $this->showPdf($template, null, null, $name);
    }
    
    public function generatePDFProjectsDeliberation($projects, $name, $url) {
        $footer = $this->twig->render('pdf/footer_page.html.twig');
        $template = $this->twig->render('pdf/projects_deliberation.html.twig', array('projects' => $projects));
        $this->create();
        $this->showPdf($template, null, $footer, $name, "PROJETS EN COURS DE DELIBERATION", "Projets");
    }

    public function generatePDFSecretariat($organizations, $name, $url) {
        $footer = $this->twig->render('pdf/footer_page.html.twig');
        $template = $this->twig->render('pdf/export_secretariat.html.twig', array('organizations' => $organizations));
        $this->create();
        $this->showPdf($template, null, $footer, $name, "LISTE DES ASSOCIATIONS", "Associations", "document_directory");
    }

    public function generatePDFProjects($projects, $name, $url) {
        $footer = $this->twig->render('pdf/footer_page.html.twig');
        $template = $this->twig->render('pdf/projects.html.twig', array('projects' => $projects, "dir" => $this->container->getParameter('kernel.project_dir')));
        $this->create();
        $this->generatePdf($template, null, $footer, $name, $url, "RECUEILS DES PROJETS", "Projets","document_directory");
    }
    
    public function showPDFProjects($projects, $name, $url) {
        $footer = $this->twig->render('pdf/footer_page.html.twig');
        $template = $this->twig->render('pdf/projects.html.twig', array('projects' => $projects, "dir" => $this->container->getParameter('kernel.project_dir')));
        $this->create();
        $this->showPdf($template, null, $footer, $name, "RECUEILS DES PROJETS", "Projets", "document_directory");
    }

    public function copyPdf($file, $from, $to) {
        $this->pdf = new Mpdf();
        $this->pdf->SetTitle($file->getSlug());
        $pages = $this->pdf->SetSourceFile($from);
        if (!file_exists($to)) {
            $handle = fopen($to, 'w');
            fclose($handle);
        }
        for ($i = 1; $i <= $pages; $i++) {
            $tplId = $this->pdf->ImportPage($i);
            $this->pdf->UseTemplate($tplId);
            if ($i != $pages) {
                $this->pdf->WriteHTML('<pagebreak />');
            }
        }
        header("Content-type:application/pdf");
        $this->pdf->Output($to, \Mpdf\Output\Destination::FILE);
    }

    public function displayFirstPage($title) {
        $html = '<div style="padding-top: 320px;text-align: center;">
                    <img src="img/logo_nif.jpg" width="300"/>
                    <br><br><br><br>
                    <h1 style="color: #94b7bc;">' . $title . '</h1>
                    <div style="color: #223457;font-size: 12px;">Edité le ' . date("d/m/Y") . '</div>
                </div>
                <pagebreak>';
        $this->pdf->WriteHTML($html);
    }

    public function displayTocWithClick($title) {
        $stylesheet = ".mpdf_toc_level_0, div.mpdf_toc{font-size: 12px;margin-top: 12px;} .tocasso{font-size: 8px;font-weight: normal;}";
        $this->pdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
        $this->pdf->TOCpagebreakByArray(array(
            'tocfont' => '',
            'tocfontsize' => '13px',
            'tocindent' => '',
            'TOCusePaging' => true,
            'TOCuseLinking' => '1',
            'toc_orientation' => '',
            'toc_mgl' => '',
            'toc_mgr' => '',
            'toc_mgt' => '',
            'toc_mgb' => '',
            'toc_mgh' => '',
            'toc_mgf' => '',
            'toc_ohname' => '',
            'toc_ehname' => '',
            'toc_ofname' => '',
            'toc_efname' => '',
            'toc_ohvalue' => 0,
            'toc_ehvalue' => 0,
            'toc_ofvalue' => 0,
            'toc_efvalue' => 0,
            'toc_preHTML' => '<div style="width:100%; text-align: center;"><h2>' . $title . '</h2></div>',
            'toc_postHTML' => '',
            'toc_bookmarkText' => '',
            'resetpagenum' => 0,
            'pagenumstyle' => '1',
            'suppress' => 'off',
            'orientation' => '',
            'toc-pageselector' => 'tocss',
            'mgl' => '',
            'mgr' => '',
            'mgt' => '',
            'mgb' => '',
            'mgh' => '',
            'mgf' => '',
            'ohname' => '',
            'ehname' => '',
            'ofname' => '',
            'efname' => '',
            'ohvalue' => 0,
            'ehvalue' => 0,
            'ofvalue' => 0,
            'efvalue' => 0,
            'toc_id' => 0,
            'pagesel' => '',
            'toc_pagesel' => '',
            'sheetsize' => '',
            'toc_sheetsize' => '',
        ));
    }

}

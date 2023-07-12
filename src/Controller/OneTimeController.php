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
use App\Entity\Phase;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;

class OneTimeController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/ot/changePhase", name="ot_changePhase", methods={"GET"})
     */
    public function ot_changePhase(Request $request) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $projects = $this->projectRepository->findAll();

        foreach ($projects as $p) {
            if ($p->getStatus() == "phase1_draft") {
                $p->setPhase($p->getPhase1());
                $p->setStatus("phase_draft");
            } elseif ($p->getStatus() == "phase1_submission") {
                $p->setPhase($p->getPhase1());
                $p->setStatus("phase_submission");
            } elseif ($p->getStatus() == "phase2_draft") {
                $phase = new Phase();

                if ($p->getPhase2() && $p->getPhase2()->getDescription() && $p->getPhase2()->getDescription() != "") {
                    $phase->setDesciption($p->getPhase2()->getDescription());
                } elseif ($p->getPhase1()) {
                    $phase->setDesciption($p->getPhase1()->getDescription());
                }

                if ($p->getPhase2() && $p->getPhase2()->getCause() && $p->getPhase2()->getCause() != "") {
                    $phase->setCause($p->getPhase2()->getCause());
                } elseif ($p->getPhase1()) {
                    $phase->setCause($p->getPhase1()->getCause());
                }

                if ($p->getPhase2() && $p->getPhase2()->getObjectif() && $p->getPhase2()->getObjectif() != "") {
                    $phase->setObjectif($p->getPhase2()->getObjectif());
                } elseif ($p->getPhase1()) {
                    $phase->setObjectif($p->getPhase1()->getObjectif());
                }

                if ($p->getPhase2() && $p->getPhase2()->getObjectif2() && $p->getPhase2()->getObjectif2() != "") {
                    $phase->setObjectif2($p->getPhase2()->getObjectif2());
                } elseif ($p->getPhase1()) {
                    $phase->setObjectif2($p->getPhase1()->getObjectif2());
                }

                if ($p->getPhase2() && $p->getPhase2()->getResources() && $p->getPhase2()->getResources() != "") {
                    $phase->setResources($p->getPhase2()->getResources());
                } elseif ($p->getPhase1()) {
                    $phase->setResources($p->getPhase1()->getResources());
                }

                if ($p->getPhase2() && $p->getPhase2()->getBeneficiary() && $p->getPhase2()->getBeneficiary() != "") {
                    $phase->setBeneficiary($p->getPhase2()->getBeneficiary());
                } elseif ($p->getPhase1()) {
                    $phase->setBeneficiary($p->getPhase1()->getBeneficiary());
                }

                if ($p->getPhase2() && $p->getPhase2()->getCost() && $p->getPhase2()->getCost() != "") {
                    $phase->setCost($p->getPhase2()->getCost());
                } elseif ($p->getPhase1()) {
                    $phase->setCost($p->getPhase1()->getCost());
                }

                if ($p->getPhase2() && $p->getPhase2()->getFunding() && $p->getPhase2()->getFunding() != "") {
                    $phase->setFunding($p->getPhase2()->getFunding());
                } elseif ($p->getPhase1()) {
                    $phase->setFunding($p->getPhase1()->getFunding());
                }

                if ($p->getPhase2() && $p->getPhase2()->getSolicitation() && $p->getPhase2()->getSolicitation() != "") {
                    $phase->setSolicitation($p->getPhase2()->getSolicitation());
                } elseif ($p->getPhase1()) {
                    $phase->setSolicitation($p->getPhase1()->getSolicitation());
                }

                if ($p->getPhase2() && $p->getPhase2()->getComment() && $p->getPhase2()->getComment() != "") {
                    $phase->setComment($p->getPhase2()->getComment());
                } elseif ($p->getPhase1()) {
                    $phase->setComment($p->getPhase1()->getComment());
                }

                if ($p->getPhase2() && $p->getPhase2()->getDuration() && $p->getPhase2()->getDuration() != "") {
                    $phase->setDuration($p->getPhase2()->getDuration());
                } elseif ($p->getPhase1()) {
                    $phase->setDuration($p->getPhase1()->getDuration());
                }

                if ($p->getPhase2() && $p->getPhase2()->getCommentNif() && $p->getPhase2()->getCommentNif() != "") {
                    $phase->setCommentNif($p->getPhase2()->getCommentNif());
                } elseif ($p->getPhase1()) {
                    $phase->setCommentNif($p->getPhase1()->getCommentNif());
                }

                if ($p->getPhase2() && $p->getPhase2()->getLocation() && $p->getPhase2()->getLocation() != "") {
                    $phase->setLocation($p->getPhase2()->getLocation());
                } elseif ($p->getPhase1()) {
                    $phase->setLocation($p->getPhase1()->getLocation());
                }

                $p->setStatus("phase_draft");
                $this->em->persist($phase);
                $p->setPhase($phase);
            } elseif ($p->getStatus() == "phase2_submission") {
                $p->setPhase($p->getPhase2());
                $p->setStatus("deliberation");
                //logs
                $this->logs[] = array("type" => "project", "action" => "project_update_status_deliberation", "project" => $p);
            } else {
                if ($p->getPhase2()) {
                    $p->setPhase($p->getPhase2());
                } else {
                    $p->setPhase($p->getPhase1());
                }
            }

            $this->em->persist($p);
            $this->em->flush();
        }

        $projects = $this->projectRepository->findAll();
        foreach ($projects as $p) {
            if ($p->getPhase()) {
                if ($p->getPhase1() && $p->getPhase1()->getId() != $p->getPhase()->getId()) {
                    $phase = $p->getPhase1();
                    $p->setPhase1(null);
                    $this->em->remove($phase);
                    $this->em->flush();
                }
                if ($p->getPhase2() && $p->getPhase2()->getId() != $p->getPhase()->getId()) {
                    $phase = $p->getPhase2();
                    $p->setPhase2(null);
                    $this->em->remove($phase);
                    $this->em->flush();
                }
                $p->setPhase1(null);
                $p->setPhase2(null);
                $this->em->persist($p);
                $this->em->flush();
            }
        }

        return $this->successReturn(array("return" => ""), 200);
    }

    /**
     * @Route("/ot/oldreserve", name="ot_oldreserve", methods={"GET"})
     */
    public function ot_oldreserve(Request $request) {
        $dateLimit = new \DateTime();
        $dateLimit->modify('last day of this month')->setTime(23, 59, 59);
        $dateNextMonth = new \DateTime();
        $dateNextMonth->add(new \DateInterval('P18D'));
        $dateNextMonth->modify('last day of this month')->setTime(23, 59, 59);
        $payments = $this->paymentRepository->getPaymentReserveWaitingReportInPast($dateLimit);
        foreach ($payments as $p) {
            $p->setDatePayment($dateNextMonth);
            $this->em->persist($p);
        }
        $this->em->flush();
        
        return $this->successReturn(array("return" => ""), 200);
    }
    
    
    /**
     * @Route("/ot/waitingreservewith0", name="ot_waitingreservewith0", methods={"GET"})
     */
    public function ot_waitingreservewith0(Request $request) {
         $projects = $this->projectRepository->findBy(array(
            "status" => "waiting_reserve"
        ));
        foreach ($projects as $project) {
            $reserve = $project->getPaymentReserve();
            if($reserve){
                if($reserve->getAmount() == 0){
                    $this->em->remove($reserve);
                    $this->changeProjectToStatus($project, "finished");
                }elseif($reserve->getTransfer() && $reserve->getReceiptValidDate() !== null){
                    $this->changeProjectToStatus($project, "finished");
                }
            }/*else{
                $this->changeProjectToStatus($project, "finished");
            }*/
        }
        $this->em->flush();
        
        return $this->successReturn(array("return" => ""), 200);
    }

}

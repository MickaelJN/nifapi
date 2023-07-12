<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Entity\Traits;
use App\Entity\Payment;

trait ProjectTrait {

    /* total alloué extension comprise */

    public function getTotalAllocated($param = "") {
        $totalAllocated = 0.00;
        $totalAllocatedReserve = 0.00;
        $totalAllocatedWithoutReserve = 0.00;

        if ($this->getInitialAllocated() && $this->getInitialAllocated()->getAmount()) {
            $totalAllocated += $this->getInitialAllocated()->getAmount();
            $totalAllocatedReserve += $this->getInitialAllocated()->getReserve();
            $totalAllocatedWithoutReserve += $this->getInitialAllocated()->getAmount() - $this->getInitialAllocated()->getReserve();
            foreach ($this->getExtensions() as $e) {
                if ($e->getDateSign()) {
                    $totalAllocated += $e->getAmount();
                    $totalAllocatedReserve += $e->getReserve();
                    $totalAllocatedWithoutReserve += ($e->getAmount() + $e->getReserve());
                }
            }
        }

        $total = $totalAllocated;
        if ($param === "reserve") {
            $total = $totalAllocatedReserve;
        } elseif ($param === "withoutReserve") {
            $total = $totalAllocatedWithoutReserve;
        }
        return round($total, 2);
    }

    /* total des paiements déjà versés */

    public function getAlreadyPayed() {
        $alreadyPayed = 0;
		if($this->getPayments() !== null){
			foreach ($this->getPayments() as $p) {
				if ($p->getTransfer() && $p->getTransfer()->getStatus() === "executed") {
					$alreadyPayed += $p->getAmount();
				}
			}
		}
        return round($alreadyPayed, 2);
    }

    /* total reste à verser */

    public function getNotAlreadyPayed() {
        $notPayed = $this->getTotalAllocated() - $this->getAlreadyPayed();
        return round($notPayed, 2);
    }

    /* total déjà en réserve par rapport aux factures déjà versées */

    public function getAlreadyInReserve() {
        if ($this->getPaymentType() === "timeline") {
            return $this->getTotalAllocated("reserve");
        } else {
            $alreadyInReserve = 0;
            foreach ($this->getInvoices() as $invoice) {
                if ($invoice->getPayment() && $invoice->getPayment()->getTransfer() && $invoice->getPayment()->getTransfer()->getStatus() === "executed") {
                    $alreadyInReserve += $invoice->getReserve();
                }
            }
            return round($alreadyInReserve, 2);
        }
    }

    public function getAlreadyAllocated() {
        if ($this->getPaymentType() === "timeline") {
            $total = 0.00;
			if($this->getPayments() !== null){
				foreach ($this->getPayments() as $payment) {
					$total += $payment->getAmount();
				}
			}
            return $total;
        } else {
            $total = $this->getAlreadyPayed() + $this->getAcceptedInvoiceNextPayment("withoutReserve");
            return round($total, 2);
            //return round($this->getTotalAllocated() - $this->getMaxAmountToPay(), 2);
        }
    }

    /* total des factures qui sont décidées mais non relié à un paiement */

    public function getAcceptedInvoiceNotInPayment($param = "") {
        $totalAccepted = 0;
        $totalAcceptedReserve = 0;
        $totalAcceptedWithoutReserve = 0;

        foreach ($this->getInvoices() as $invoice) {
            if (!$invoice->getPayment() && ($invoice->getStatus() === "valid" || $invoice->getStatus() === "updated")) {
                $totalAccepted += $invoice->getAmountToPay() + $invoice->getReserve();
                $totalAcceptedReserve += $invoice->getReserve();
                $totalAcceptedWithoutReserve += $invoice->getAmountToPay();
            }
        }

        $total = $totalAccepted;
        if ($param === "reserve") {
            $total = $totalAcceptedReserve;
        } elseif ($param === "withoutReserve") {
            $total = $totalAcceptedWithoutReserve;
        }
        return round($total, 2);
    }

    /* total des factures qui sont décidées mais non relié à un paiement */

    public function getAcceptedInvoiceNextPayment($param = "") {
        $totalAccepted = 0;
        $totalAcceptedReserve = 0;
        $totalAcceptedWithoutReserve = 0;

        foreach ($this->getInvoices() as $invoice) {
            if ($invoice->getPayment() && $invoice->getPayment()->getTransfer() && $invoice->getPayment()->getTransfer()->getStatus() !== "executed" && ($invoice->getStatus() === "valid" || $invoice->getStatus() === "updated")) {
                $totalAccepted += $invoice->getAmountToPay() + $invoice->getReserve();
                $totalAcceptedReserve += $invoice->getReserve();
                $totalAcceptedWithoutReserve += $invoice->getAmountToPay();
            }
        }

        $total = $totalAccepted;
        if ($param === "reserve") {
            $total = $totalAcceptedReserve;
        } elseif ($param === "withoutReserve") {
            $total = $totalAcceptedWithoutReserve;
        }
        return round($total, 2);
    }

    public function getMaxAmountPriseEnCharge() {
        //$maxWithreserve = $this->getTotalAllocated() - $this->getAlreadyPayed() - $this->getTotalAllocated("reserve") - $this->getAcceptedInvoiceNotInPayment("withoutReserve") - $this->getAcceptedInvoiceNextPayment("withoutReserve");
        $maxWithreserve = $this->getTotalAllocated() - $this->getAlreadyPayed() - $this->getAcceptedInvoiceNotInPayment() - $this->getAcceptedInvoiceNextPayment();
        return round($maxWithreserve, 2);
    }
    
    public function getMaxAmountPriseEnChargeWithoutReserve() {
        $maxWithreserve = $this->getTotalAllocated() - $this->getAlreadyPayed() - $this->getTotalAllocated("reserve") - $this->getAcceptedInvoiceNotInPayment("withoutReserve") - $this->getAcceptedInvoiceNextPayment("withoutReserve");
        return round($maxWithreserve, 2);
    }

    public function getMaxAmountToPay() {
        if ($this->getPaymentType() === "timeline") {
            return $this->getTotalAllocated() - $this->getAlreadyAllocated();
        } else {
            $total = $this->getMaxAmountPriseEnCharge() / (1 - ($this->getPercentageReserve() / 100));
            return round($total, 2);
        }
    }

    public function getMaxAmountValid() {
        if ($this->getPaymentType() === "timeline") {
            return $this->getMaxAmountToPay();
        } else {
            $max = $this->getMaxAmountToPay();
            if ($max > 0) {
                /*if ($this->getPercentageReserve()) {
                    $max = $max / (1 - ($this->getPercentageReserve() / 100));
                    //$max = $max * (1 - ($this->getPercentageReserve() / 100));
                }*/
                if ($this->getPercentage() && $this->getPercentage() < 100) {
                    //$max = $max / (1 - ((100 - $this->getPercentage()) / 100));
                    $max = $max / ($this->getPercentage() / 100);
                }
                return round($max, 2);
            }
            return 0.00;
        }
    }

    public function getReserveReste() {
        if ($this->getPaymentType() === "timeline") {
            return 0;
        } else {
            return round(($this->getTotalAllocated("reserve") - $this->getAlreadyInReserve() - $this->getAcceptedInvoiceNextPayment("reserve") - $this->getAcceptedInvoiceNotInPayment("reserve")), 2);
        }
    }

    public function removeAllPayments(): self {
		if($this->getPayments() !== null){
			foreach ($this->getPayments() as $payment) {
				if ($this->payments->removeElement($payment)) {
					// set the owning side to null (unless already changed)
					if ($payment->getProject() === $this) {
						$payment->setProject(null);
					}
				}
			}
		}
        return $this;
    }

    public function getPaymentReserve(): ?Payment {
		if($this->getPayments() !== null){
			foreach ($this->getPayments() as $payment) {
				if ($payment->isReserve()) {
					return $payment;
				}
			}
		}
        return null;
    }

    public function removePaymentReserve(): self {
        $reserve = $this->getPaymentReserve();
        if ($reserve) {
            $this->removePayment($reserve);
        }
        return $this;
    }

    public function removePaymentNotInTransfer(): self {
        foreach ($this->getPayments() as $payment) {
            if ($payment->isReserve() === false && $payment->getTransfer() === null) {
                $this->removePayment($payment);
            }
        }
        return $this;
    }
    
    public function refusedAllNewInvoice($last = false): self {
        $cause = "Le projet a quitté la phase en cours. Les factures restantes ont donc toutes été refusées.";
        if ($last) {
            $cause = "Aucune facture supplémentaire ne peut être acceptée car le projet a déjà consommé tout le montant alloué ou a été changé de statut par l'administrateur.";
        }
        foreach ($this->getInvoices() as $invoice) {
            if ($invoice->getStatus() === "new") {
                $invoice->setStatus("refused");
                $invoice->setDateDecision(new \DateTime());
                $invoice->setPercentage(null);
                $invoice->setAmountValid(null);
                $invoice->setAmountToPay(null);
                $invoice->setCause($cause);
                $invoice->setCauseAuto($last ? "last" : "changeStatus");
                $invoice->setReserve(null);
                $invoice->setReservePercentage(null);
            }
        }
        return $this;
    }
    
    public function newAllRefusedAutoInvoice(): self {
       foreach ($this->getInvoices() as $invoice) {
            if ($invoice->getStatus() === "refused" && $invoice->getCauseAuto() !== null) {
                $invoice->setStatus("new");
                $invoice->setDateDecision(null);
                $invoice->setPercentage(null);
                $invoice->setAmountValid(null);
                $invoice->setAmountToPay(null);
                $invoice->setCause(null);
                $invoice->setCauseAuto(null);
                $invoice->setReserve(null);
                $invoice->setReservePercentage(null);
            }
        }
        return $this;
    }

    public function getFinalPriseEnCharge() {
        return $this->getAlreadyPayed() - $this->getRefundAmountToPay();
    }

    public function getRefundAmountToPay() {
        if ($this->getRefund()) {
            return $this->getRefund()->getAmountToPay();
        }
        return 0.00;
    }
    
    public function isInNextPayment(){
		if($this->getPayments() !== null){
			foreach($this->getPayments() as $payment){
				if($payment->getTransfer() && $payment->getTransfer()->getStatus() !== "executed"){
					return true;
				}
			}
		}
        return false;
    }
    
    public function getLastMessage(){
        $m = null;
        foreach($this->getMessages() as $message){
            $m = $message;
        }
        return $m;
    }
    
}

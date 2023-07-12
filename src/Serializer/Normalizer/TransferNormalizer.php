<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class TransferNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface {

    private $normalizer;
    private $em;
    private $security;
    private $parameterBag;

    public function __construct(ObjectNormalizer $normalizer, EntityManagerInterface $em, Security $security, ParameterBagInterface $parameterBag) {
        $this->normalizer = $normalizer;
        $this->em = $em;
        $this->security = $security;
        $this->parameterBag = $parameterBag;
    }

    public function normalize($object, $format = null, array $context = []): array {
        $data = $this->normalizer->normalize($object, $format, $context);

        $groups = is_array($context['groups']) ? $context['groups'] : array($context['groups']);
        if (in_array("transferfull:read", $groups)) {

            $totalPayment = 0.00;
            $sepa = 0;
            $sepaAmount = 0.00;
            $nonSepa = 0;
            $nonSepaAmount = 0.00;
            $payments = $object->getPayments();
            foreach ($payments as $payment) {
                $totalPayment += $payment->getAmount();
                if ($payment->getProject()->getOrganization()->getRib()->getIsSepa()) {
                    $sepaAmount += $payment->getAmount();
                    $sepa++;
                } else {
                    $nonSepaAmount += $payment->getAmount();
                    $nonSepa++;
                }
            }

            $data["total"] = $totalPayment;
            $data["sepa"] = $sepa;
            $data["nonSepa"] = $nonSepa;
            $data["sepaAmount"] = $sepaAmount;
            $data["nonSepaAmount"] = $nonSepaAmount;

            if ($this->parameterBag->get("fakedate") && $this->parameterBag->get("fakedate") == 1) {
                $fakedate = $this->em->getRepository("App\Entity\AppParameters")->findOneBy(array("name" => "fakedate"));
                $date = new \DateTime($fakedate->getData()["date"]);
                $date = $date->format("Y-m-d") . " " . date("H:i:s");
                $newFakedate = new \DateTime($date);
                $data["dateNow"] = $newFakedate->format("Y-m-d H:i:s");
            } else {
                $data["dateNow"] = new \DateTime();
            }
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof \App\Entity\Transfer;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }

    public function getDateNow() {
        $date = null;
        if ($this->parameterBag->get("fakedate")) {
            $fakedate = $this->em->getRepository("App\Entity\AppParameters")->findOneBy(array("name" => "fakedate"));
            $date = new \DateTime($fakedate->getData()["date"]);
            return $date;
        }
        $date = new \DateTime();
        return $date;
    }

}

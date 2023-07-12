<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PaymentNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface {

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
        if (in_array("paymentfull:read", $groups) || in_array("projectfull:read", $groups)) {
            $next = null;
            if ($object->getTransfer() && $object->getTransfer()->getStatus("executed") && !$object->getReport()) {
                $paymentNext = $this->em->getRepository("App\Entity\Payment")->nextPaymentByPayment($object);
                if ($paymentNext) {
                    $next = array(
                        "id" => $paymentNext->getId(),
                        "datePayment" => $paymentNext->getDatePayment()->format('Y-m-d'),
                        "isReserve" => $paymentNext->isReserve(),
                        "days" => date_diff($this->getDateNow(), $paymentNext->getDatePayment())->format("%a")
                    );
                }
            }
            $data["paymentNext"] = $next;
        }

        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof \App\Entity\Payment;
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

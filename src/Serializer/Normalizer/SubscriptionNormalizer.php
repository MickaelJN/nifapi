<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SubscriptionNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface {

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
        if (in_array("subscriptionfull:read", $groups)) {
            $sameNumber = null;
            if (array_key_exists("identificationNumber", $object->getData()) && $object->getData()["identificationNumber"] != "") {
                $sameNumber = $this->em->getRepository("App\Entity\Organization")->findOneBy(array("identificationNumber" => $object->getData()["identificationNumber"]));
                $data["controlSameNumber"] = $sameNumber !== null ? $sameNumber->getName() : false;
            }
        }
        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof \App\Entity\Subscription;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }

}

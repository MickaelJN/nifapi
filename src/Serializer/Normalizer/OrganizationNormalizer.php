<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class OrganizationNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface {

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
        if (in_array("organization:read", $groups) || in_array("organizationfull:read", $groups)) {
            $one_year = new \DateInterval("P1Y");
            $one_year->invert = 1;
            $one_year_ago = new \DateTime();
            $one_year_ago->add($one_year);
            $data["annexeStatusOld"] = $object->getAnnexeStatus() !== null ? $object->getAnnexeStatus()->getCreatedAt() <= $one_year_ago : false;
        }
        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof \App\Entity\Organization;
    }

    public function hasCacheableSupportsMethod(): bool {
        return true;
    }

}

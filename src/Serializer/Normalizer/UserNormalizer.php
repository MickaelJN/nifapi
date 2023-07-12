<?php

namespace App\Serializer\Normalizer;

use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface {

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
        if (in_array("user:read", $groups)) {
            if ($object->getType() !== "association") {
                /*$projects = $this->em->getRepository("App\Entity\Project")->findBy(array("manager" => $object), array("number" => "DESC"));
                $data["projects"] = array();
                foreach ($projects as $p) {
                    $data["projects"][] = array(
                        "id" => $p->getId(),
                        "name" => $p->getName(),
                        "number" => $p->getNumber(),
                        "status" => $p->getStatus(),
                        "organization" => array("name" => $p->getOrganization()->getName())
                    );
                }*/
                $data["projectCount"] = count($projects = $this->em->getRepository("App\Entity\Project")->findBy(array("manager" => $object)));
            } else {
                if ($object->getOrganization()) {
                    $data["organization"] = array(
                        "id" => $object->getOrganization()->getId(),
                        "name" => $object->getOrganization()->getName(),
                        "representative" => $object->getOrganization()->getRepresentative()->getId()
                    );
                }
                $data["projectCount"] = count($projects = $this->em->getRepository("App\Entity\Project")->findBy(array("contact" => $object)));
            }
        }
        return $data;
    }

    public function supportsNormalization($data, $format = null): bool {
        return $data instanceof \App\Entity\User;
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

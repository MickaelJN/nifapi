<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\EntityManagerInterface;

class UserChecker implements UserCheckerInterface {

    private $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function checkPreAuth(UserInterface $user): void {
        if (!$user instanceof AppUser) {
            return;
        }

        $isMaintenance = $this->em->getRepository("App\Entity\AppParameters")->findOneBy(array("name" => "maintenance"));
        if ($isMaintenance && $isMaintenance->getData() && array_key_exists("isActive", $isMaintenance->getData()) && $isMaintenance->getData()["isActive"] == true) {
            throw new CustomUserMessageAccountStatusException('Vous ne pouvez pas vous connecter durant la maintenance');
        }

        if (!$user->getIsActive()) {
            // the message passed to this exception is meant to be displayed to the user
            throw new CustomUserMessageAccountStatusException('Cet compte a été désactivé');
        }

        if ($user->getVerifyCode()) {
            $user->setVerifyCode(null);
            $user->setVerifyCodeDate(null);
            $this->em->persist($user);
            $this->em->flush();
        }
    }

    public function checkPostAuth(UserInterface $user): void {
        if (!$user instanceof AppUser) {
            return;
        }

        // user account is expired, the user may be notified
        /* if ($user->isExpired()) {
          throw new AccountExpiredException('...');
          } */
    }

}

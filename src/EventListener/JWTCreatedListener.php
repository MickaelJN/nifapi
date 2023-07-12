<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class JWTCreatedListener {

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack) {
        $this->requestStack = $requestStack;
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event) {
        $request = $this->requestStack->getCurrentRequest();
        $user = $event->getUser();
        $requestData = json_decode($request->getContent(), true);

        $payload = $event->getData();

        $payload['data'] = array(
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'lastname' => $user->getLastname(),
            'firstname' => $user->getFirstname(),
            'type' => $user->getType(),
            'position' => $user->getPosition(),
            'remember' => $requestData["remember"]
        );
        if ($user->getType() === "association" && $user && $user->getOrganization()) {
            $payload['data']['organization']['id'] = $user->getOrganization()->getId();
            $payload['data']['organization']['name'] = $user->getOrganization()->getName();
            $payload['data']['organization']['representative']['id'] = $user->getOrganization()->getRepresentative()->getId();
            $payload['data']['contactProjects'] = $user->getContactProjects();
        } else {
            $payload['data']['isAdmin'] = $user->getIsAdmin();
            $payload['data']['isSecretariat'] = $user->getIsSecretariat();
            $payload['data']['isSecretariatSupport'] = $user->getIsSecretariatSupport();
            $payload['data']['isPresident'] = $user->getIsPresident();
            $payload['data']['sign'] = $user->getSign();
        }

        $event->setData($payload);
    }

}

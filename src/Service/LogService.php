<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\LogApi;
use App\Entity\LogAction;
use App\Entity\Message;

class LogService {

    private $em;
    private $requestStack;
    private $serializer;
    private $security;

    public function __construct(
            EntityManagerInterface $em,
            RequestStack $requestStack,
            SerializerInterface $serializer,
            Security $security
    ) {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->serializer = $serializer;
        $this->security = $security;
    }

    public function addLogs($return, $code, $logs = []) {
        if (is_array($logs) && !empty($logs)) {
            $logApi = $this->addLogApi($return, $code);
            foreach ($logs as $log) {
                $this->addLogAction($log, $logApi);
            }
        }
    }

    public function addLogApi($return, $code) {
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $requestData = json_decode($request->getContent(), true);

        $logApi = new LogApi();
        $logApi->setDate(new \DateTime());
        $logApi->setUser($user);

        if (is_array($requestData)) {
            if (array_key_exists("newpassword", $requestData)) {
                $dataOther["newpassword"] = "**********";
            }
            if (array_key_exists("passwordConfirmation", $requestData)) {
                $dataOther["passwordConfirmation"] = "**********";
            }
        }

        $logApi->setDataSend($requestData);
        $logApi->setPath($request->getUri());
        $logApi->setMethod($request->getMethod());
        $logApi->setCodeHttp($code);
        // $logApi->setReturnValue($return);

        $this->em->persist($logApi);
        $this->em->flush();
        return $logApi;
    }

    public function addLogAction($log, $logApi = null) {
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $requestData = json_decode($request->getContent(), true);
        $dataOther = $requestData ? $requestData : [];

        $logAction = new LogAction();
        $logAction->setLogApi($logApi);
        $logAction->setAuthor($user);
        $logAction->setType(array_key_exists("type", $log) ? $log["type"] : null);
        $logAction->setAction(array_key_exists("action", $log) ? $log["action"] : null);
        $logAction->setProject(array_key_exists("project", $log) ? $log["project"] : null);
        $logAction->setOrganization(array_key_exists("organization", $log) ? $log["organization"] : null);
        $logAction->setUser(array_key_exists("user", $log) ? $log["user"] : null);
        $logAction->setTransfer(array_key_exists("transfer", $log) ? $log["transfer"] : null);
        if (array_key_exists("data", $log) && $log["data"] && $log["data"] != null && is_array($log["data"]) && !empty($log["data"])) {
            if (!$dataOther && !is_array($dataOther)) {
                $dataOther = [];
            }
            $dataOther = array_merge($dataOther, $log["data"]);
        }
        if ($user) {
            $dataOther["author"] = array("lastname" => $user->getLastname(), "firstname" => $user->getFirstname());
        }

        if (array_key_exists("newpassword", $dataOther)) {
            $dataOther["newpassword"] = "**********";
        }
        if (array_key_exists("passwordConfirmation", $dataOther)) {
            $dataOther["passwordConfirmation"] = "**********";
        }

        $logAction->setData($dataOther);
        $logAction->setDate(new \DateTime());
        $this->em->persist($logAction);
        $this->em->flush();
        $this->addMessageAuto($logAction);
        return $logAction;
    }

    public function addMessage($type, $log, $content = null, $isAuto = false) {

        $message = new Message();
        $message->setLog($log);
        $message->setDate(new \DateTime());
        $message->setType($type);
        $message->setContent($content);
        $message->setProject($log->getProject());
        $message->setUser($log->getAuthor());
        $message->setData($log->getData());
        $message->setDate(new \DateTime());

        if ($isAuto) {
            $message->setUser(null);
        }

        $this->em->persist($message);
        $this->em->flush();

        if ($log->getProject()) {
            $project = $log->getProject();
            if (in_array($log->getAction(), array(
                        "project_update_manager",
                        "project_update_refund_confirm",
                        "project_add",
                        "project_add_receipt",
                        "project_update_status_phase_draft_correction",
                        "project_update_status_phase_draft",
                        "project_update_status_phase_submission",
                        "project_update_status_in_progress",
                        "project_update_status_waiting_final_report",
                        "project_update_status_waiting_reserve",
                        "project_update_status_finished",
                        "project_update_status_canceled",
                        "project_update_status_refusal",
                        "project_refund_ask_refund",
                        "project_waiting_report",
                        "project_refused_report",
                        "project_site_texte_tovalid",
                        "project_update_status_configuration",
                        "project_update_status_deliberation"
                    ))) {
                $nb = $project->getMessageContactNew() ? $project->getMessageContactNew() : 0;
                $project->setMessageContactNew($nb + 1);
            }
            if (in_array($log->getAction(), array(
                        "project_update_extension_sign",
                        "project_add_annexe",
                        "project_update_contact",
                        "project_update_validation_sign",
                        "project_add",
                        "project_update_status_phase_submission",
                        "project_update_status_finished",
                    ))) {
                $nb = $project->getMessageManagerNew() ? $project->getMessageManagerNew() : 0;
                $project->setMessageManagerNew($nb + 1);
            }
            $this->em->persist($project);
            $this->em->flush();
        }
    }

    public function addMessageAuto($log) {

        $typeMessage = array(
            "project_update_extension_sign",
            "project_add_annexe",
            "project_update_manager",
            "project_update_contact",
            "project_update_refund_confirm",
            "project_update_validation_sign",
            "project_add",
            "project_introduction",
            "project_add_receipt",
            "project_update_status_phase_draft_correction",
            "project_update_status_phase_draft",
            "project_update_status_phase_submission",
            "project_update_status_in_progress",
            "project_update_status_waiting_final_report",
            "project_update_status_waiting_reserve",
            "project_update_status_finished",
            "project_update_status_canceled",
            "project_update_status_refusal",
            "project_refund_ask_refund",
            "project_waiting_report",
            "project_refused_report",
            "project_site_texte_tovalid",
            "project_update_status_configuration",
            "project_update_status_deliberation"
        );

        if (in_array($log->getAction(), $typeMessage)) {
            $this->addMessage($log->getAction(), $log, null, true);
        }
    }

}

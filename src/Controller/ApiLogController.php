<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use App\Repository\ProjectRepository;
use App\Entity\Message;
use App\Entity\File;
use App\Repository\PaymentRepository;
use App\Service\MPdfService;
use App\Utils\MyUtils;

class ApiLogController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/api/logs", name="api_logs_get", methods={"GET"})
     */
    public function getLogs(Request $request) {
        $json = null;
        $data = $request->request->all();
        $limit = $request->query->get('limit') ? $request->query->get('limit') : 100;
        $page = $request->query->get('page') ? $request->query->get('page') : 1;
        $offset = $page > 1 ? ($page - 1) * $limit : 0;
        $project = $request->query->get('project') && $request->query->get('project') != "" ? $request->query->get('project') : null;
        $organization = $request->query->get('organization') && $request->query->get('organization') != "" ? $request->query->get('organization') : null;
        $user = $request->query->get('user') && $request->query->get('user') != "" ? $request->query->get('user') : null;
        $author = $request->query->get('author') && $request->query->get('author') != "" ? $request->query->get('author') : null;
        $action = $request->query->get('action') && $request->query->get('action') != "" ? $request->query->get('action') : null;
        

        $logs = $this->logActionRepository->filterLogs($project, $organization, $user, $author, $action, $limit, $offset);
        $jsonLogs = json_decode($this->serializer->serialize(
                        $logs,
                        'json',
                        ['groups' => array("log:read")]
                ), true);

        $total = count($this->logActionRepository->filterLogs($project, $organization, $user, $author, $action));
        $pageTotal = ceil($total / $limit);

        $json = array(
            "page" => 1,
            "nb" => $total,
            "pageTotal" => $pageTotal,
            "data" => $jsonLogs
        );

        if ($request->query->get('filterInit')) {
            $json["projects"] = json_decode($this->serializer->serialize($this->projectRepository->findBy(array(), array("number" => "DESC", "name" => "ASC")),'json',['groups' => array("projectshort:read")]), true);
            $json["organizations"] = json_decode($this->serializer->serialize($this->organizationRepository->findBy(array(), array("name" => "ASC")),'json',['groups' => array("organizationshort:read")]), true);
            $json["users"] = json_decode($this->serializer->serialize($this->userRepository->findBy(array(), array("lastname" => "ASC", "firstname" => "ASC")),'json',['groups' => array("userlog:read")]), true);
            $json["actions"] = $this->logActionRepository->getAllActions();
        }

        return $this->successReturn($json, 200);
    }

}

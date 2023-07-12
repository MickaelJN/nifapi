<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Organization;
use App\Entity\Country;
use App\Entity\User;
use App\Entity\Photo;
use App\Entity\Project;
use App\Entity\Phase;
use App\Entity\File;
use App\Entity\AllocatedAmount;
use App\Entity\OrganizationLocal;
use App\Entity\Payment;
use App\Entity\Transfer;
use App\Entity\Rib;
use App\Entity\Report;
use App\Entity\Invoice;
use App\Entity\Refund;
use App\Entity\Subscription;
use App\Entity\LogAction;
use App\Entity\Message;

class ImportWPController extends AbstractController {

    use ControllerTrait;

    /**
     * @Route("/importWPUnder146", name="importWPUnder146", methods={"GET"})
     */
    public function importWPUnder146() {

        set_time_limit(0);
        @ini_set("memory_limit", -1);

        $json = file_get_contents($this->getParameter('site_wp').'layout/themes/nif/export/export_json.php');
        $data = json_decode($json, true);

        $defaultManager = $this->getDefaultManager();

        $sameOrganization = array();
        $sameOrganization["n92"] = array("reference" => 23, "active" => false);
        $sameOrganization["n3"] = array("reference" => 151, "active" => false);
        $sameOrganization["n73"] = array("reference" => 280, "active" => false);
        $sameOrganization["n141"] = array("reference" => 280, "active" => false);
        $sameOrganization["n116"] = array("reference" => 34, "active" => true);
        $sameOrganization["n59"] = array("reference" => 30, "active" => false);
        $sameOrganization["n60"] = array("reference" => 30, "active" => false);
        $sameOrganization["n84"] = array("reference" => 149, "active" => false);
        $sameOrganization["n76"] = array("reference" => 149, "active" => false);
        $sameOrganization["n107"] = array("reference" => 149, "active" => false);
        $sameOrganization["n74"] = array("reference" => 156, "active" => false);
        $sameOrganization["n100"] = array("reference" => 156, "active" => false);
        $sameOrganization["n134"] = array("reference" => 104, "active" => false);
        $sameOrganization["n32"] = array("reference" => 189, "active" => false);
        $sameOrganization["n36"] = array("reference" => 189, "active" => false);
        $sameOrganization["n95"] = array("reference" => 189, "active" => false);
        $sameOrganization["n56"] = array("reference" => 189, "active" => false);
        $sameOrganization["n108"] = array("reference" => 189, "active" => false);
        $sameOrganization["n125"] = array("reference" => 189, "active" => false);
        $sameOrganization["n71"] = array("reference" => 150, "active" => false);
        $sameOrganization["n87"] = array("reference" => 150, "active" => false);
        $sameOrganization["n102"] = array("reference" => 150, "active" => false);
        $sameOrganization["n117"] = array("reference" => 150, "active" => false);
        $sameOrganization["n129"] = array("reference" => 150, "active" => false);
        $sameOrganization["n53"] = array("reference" => 150, "active" => false);
        $sameOrganization["n26"] = array("reference" => 256, "active" => false);
        $sameOrganization["n27"] = array("reference" => 256, "active" => false);
        $sameOrganization["n93"] = array("reference" => 10, "active" => false);
        $sameOrganization["n58"] = array("reference" => 10, "active" => false);
        $sameOrganization["n106"] = array("reference" => 75, "active" => false);
        $sameOrganization["n6"] = array("reference" => 154, "active" => false);
        $sameOrganization["n14"] = array("reference" => 154, "active" => false);
        $sameOrganization["n69"] = array("reference" => 154, "active" => false);
        $sameOrganization["n139"] = array("reference" => 154, "active" => false);
        $sameOrganization["n140"] = array("reference" => 168, "active" => false);
        $sameOrganization["n57"] = array("reference" => 168, "active" => false);
        $sameOrganization["n31"] = array("reference" => 159, "active" => false);
        $sameOrganization["n81"] = array("reference" => 159, "active" => false);
        $sameOrganization["n113"] = array("reference" => 159, "active" => false);
        $sameOrganization["n143"] = array("reference" => 28, "active" => false);
        $sameOrganization["n120"] = array("reference" => 62, "active" => false);
        $sameOrganization["n124"] = array("reference" => 18, "active" => false);
        $sameOrganization["n66"] = array("reference" => 33, "active" => false);
        $sameOrganization["n91"] = array("reference" => 187, "active" => false);
        $sameOrganization["n101"] = array("reference" => 187, "active" => false);
        $sameOrganization["n13"] = array("reference" => 170, "active" => false);
        $sameOrganization["n19"] = array("reference" => 170, "active" => false);
        $sameOrganization["n70"] = array("reference" => 170, "active" => false);
        $sameOrganization["n90"] = array("reference" => 170, "active" => false);
        $sameOrganization["n67"] = array("reference" => 170, "active" => false);
        $sameOrganization["n121"] = array("reference" => 170, "active" => false);
        $sameOrganization["n122"] = array("reference" => 170, "active" => false);
        $sameOrganization["n142"] = array("reference" => 292, "active" => false);
        $sameOrganization["n7"] = array("reference" => 163, "active" => false);
        $sameOrganization["n55"] = array("reference" => 163, "active" => false);
        $sameOrganization["n109"] = array("reference" => 97, "active" => false);
        $sameOrganization["n118"] = array("reference" => 97, "active" => false);
        $sameOrganization["n128"] = array("reference" => 177, "active" => false);
        $sameOrganization["n64"] = array("reference" => 171, "active" => false);
        $sameOrganization["n110"] = array("reference" => 171, "active" => false);
        $sameOrganization["n111"] = array("reference" => 161, "active" => false);
        $sameOrganization["n65"] = array("reference" => 161, "active" => false);
        $sameOrganization["n37"] = array("reference" => 161, "active" => false);
        $sameOrganization["n12"] = array("reference" => 161, "active" => false);
        $sameOrganization["n94"] = array("reference" => 1, "active" => false);
        $sameOrganization["n39"] = array("reference" => 5, "active" => false);
        $sameOrganization["n135"] = array("reference" => 5, "active" => false);
        $sameOrganization["n35"] = array("reference" => 152, "active" => false);
        $sameOrganization["n63"] = array("reference" => 152, "active" => false);
        $sameOrganization["n78"] = array("reference" => 152, "active" => false);
        $sameOrganization["n98"] = array("reference" => 152, "active" => false);
        $sameOrganization["n112"] = array("reference" => 152, "active" => false);
        $sameOrganization["n137"] = array("reference" => 152, "active" => false);
        $sameOrganization["n136"] = array("reference" => 190, "active" => false);
        $sameOrganization["n89"] = array("reference" => 321, "active" => false);
        $sameOrganization["n79"] = array("reference" => 47, "active" => false);
        $sameOrganization["n61"] = array("reference" => 47, "active" => false);
        $sameOrganization["n21"] = array("reference" => 197, "active" => false);
        $sameOrganization["n42"] = array("reference" => 197, "active" => false);
        $sameOrganization["n119"] = array("reference" => 197, "active" => false);

        $secteurs = array();
        $secteurs["s7"] = 1;
        $secteurs["s8"] = 6;
        $secteurs["s9"] = 2;
        $secteurs["s10"] = 3;
        $secteurs["s11"] = 5;
        $secteurs["s12"] = 2;
        $secteurs["s13"] = 8;
        $secteurs["s14"] = 5;
        $secteurs["s"] = 8;

        $isInvoice = array(1, 3, 7, 8, 17, 20, 26, 27, 28, 29, 33, 35, 38, 43, 44, 48, 50, 54, 55, 62, 63, 66, 68, 77, 78, 86, 88, 89, 91, 94, 96, 97, 98, 99, 101, 103, 105, 109, 112, 115, 118, 120, 123, 124, 127, 128, 133, 136, 137, 138, 143, 144, 145);

        foreach ($data as $d) {
            echo $d["number"] . "<br>";
            //organization
            $newOrganization = true;
            $organization = null;
            if (array_key_exists("n" . $d["number"], $sameOrganization)) {
                $project = $this->projectRepository->findOneBy(array("number" => $sameOrganization["n" . $d["number"]]["reference"]));
                if ($project) {
                    $organization = $project->getOrganization();
                    $newOrganization = false;
                }
            }
            if (!$organization) {
                $organization = new Organization();
                $organization->setName($this->cleanName($d["association"]));
                $organization->setWebsite($d["association"]);
                $organization->setIsConfirm(false);
                $this->em->persist($organization);
            }

            //project
            $project = new Project();
            $project->setNumber((int) $d["number"]);
            $project->setOrganization($organization);
            $project->setName($d["name"]);
            $secteur = $this->secteurRepository->findOneById($secteurs["s" . $d["secteur"]]);
            $project->setSecteur($secteur);

            if (strpos($d["date_dexecution"], "/") !== false) {
                $date = explode("/", $d["date_dexecution"]);
                $dateBegin = new \DateTime($date[0] . "-01-01");
                $project->setDateBegin($dateBegin);
                $dateEnd = new \DateTime($date[1] . "-12-31");
                $project->setDateEnd($dateEnd);
            } else {
                $dateBegin = new \DateTime($d["date_dexecution"] . "-01-01");
                $project->setDateBegin($dateBegin);
                $dateEnd = new \DateTime($d["date_dexecution"] . "-12-31");
                $project->setDateEnd($dateEnd);
            }
            $project->setManager($defaultManager);
            $project->setMessageManagerNew(0);
            $project->setMessageContactNew(0);

            $localisation = ($d["lieu_dexecution"] && trim($d["lieu_dexecution"]) != "") ? trim($d["lieu_dexecution"]) : null;
            $project->setLocation($localisation);

            $project->setStatus("finished");
            $project->setPaymentType(in_array((int) $d["number"], $isInvoice) ? "invoice" : "timeline");

            if ($organization && !$newOrganization && array_key_exists("n" . $d["number"], $sameOrganization) && $sameOrganization["n" . $d["number"]]["reference"] > 145) {
                $project->setContact($this->getContact($organization));
                $project->setIsContactValid($project->getContact()->getId() != $organization->getRepresentative()->getId());
            }
            $project->setContactValidationSend(null);
            $project->setContactValidationId(null);

            $initialAllocated = new AllocatedAmount();
            $initialAllocated->setAmount((int) str_replace(".", "", $d["montant"]));

            $dateValidExplode = explode(" ", $d["date_confirmation"]);
            //$dateValidExplode = explode("-", $dateValidExplode[0]);
            $dateValidExplode = explode("/", $dateValidExplode[0]);
            $dateValid = new \DateTime($dateValidExplode[2] . "-" . $dateValidExplode[1] . "-" . $dateValidExplode[0]);

            $initialAllocated->setDateAllocated($dateValid);
            $initialAllocated->setReserve(0);
            $initialAllocated->setDateSign($dateValid);
            $initialAllocated->setDateCheck($dateValid);
            if ($organization && !$newOrganization && array_key_exists("n" . $d["number"], $sameOrganization) && $sameOrganization["n" . $d["number"]]["reference"] > 145) {
                $dataImport = $this->generateDataAllocatedImport($project);
                $initialAllocated->setData($dataImport);
            }

            $project->setInitialAllocated($initialAllocated);
            $project->setWebTexte($d["content"]);
            $project->setWebEvolution($d["evolution"]);
            $project->setDataWP($d);
            $project->setWebStatus("publish");

            $this->em->persist($project);
            $this->em->flush();
        }

        return $this->json([], 200);
    }

    /**
     * @Route("/importWPUpper146", name="importWPUpper146", methods={"GET"})
     */
    public function importWPUpper146() {

        set_time_limit(0);
        @ini_set("memory_limit", -1);

        $json = file_get_contents($this->getParameter('site_wp').'layout/themes/nif/export/export_json146.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            echo $d["number"] . "<br>";

            //project
            $project = $this->projectRepository->findOneBy(array("number" => $d["number"]));
            $project->setWebTexte($d["content"]);
            $project->setWebEvolution($d["evolution"]);
            $project->setDataWP($d);
            $project->setWebStatus("publish");

            $this->em->persist($project);
            $this->em->flush();
        }

        return $this->json([], 200);
    }

    /**
     * @Route("/importWPPhoto", name="importWPPhoto", methods={"GET"})
     */
    public function importWPPhoto() {
        $projects = $this->projectRepository->findBy(array(), array("number" => "ASC"));
        foreach ($projects as $project) {
            $data = $project->getDataWP();
            if (is_array($project->getDataWP())) {
                if (array_key_exists("photo", $data) && $data["photo"] != null) {
                    $extension = explode(".", $data["photo"])[3];
                    $slug = $this->myUtils->slugify($project->getNumber() . "-" . str_replace(".", "", $project->getName()) . "1");
                    $this->uploadWPPHoto($data["photo"], $slug . "." . $extension);
                    $photo = new Photo();
                    $photo->setProject($project);
                    $photo->setSlug($slug);
                    $photo->setExtension($extension);
                    $photo->setCreatedAt(new \DateTime());
                    $photo->setPosition(0);
                    $photo->setSelected(true);
                    $this->em->persist($photo);
                    $this->em->flush();
                }
                if (array_key_exists("photo2", $data) && $data["photo2"] != null) {
                    $extension = explode(".", $data["photo2"])[3];
                    $slug = $this->myUtils->slugify($project->getNumber() . "-" . str_replace(".", "", $project->getName()) . "2");
                    $this->uploadWPPHoto($data["photo2"], $slug . "." . $extension);
                    $photo = new Photo();
                    $photo->setProject($project);
                    $photo->setSlug($slug);
                    $photo->setExtension($extension);
                    $photo->setCreatedAt(new \DateTime());
                    $photo->setPosition(1);
                    $photo->setSelected(true);
                    $this->em->persist($photo);
                    $this->em->flush();
                }
                if (array_key_exists("photo3", $data) && $data["photo3"] != null) {
                    $extension = explode(".", $data["photo3"])[3];
                    $slug = $this->myUtils->slugify($project->getNumber() . "-" . str_replace(".", "", $project->getName()) . "3");
                    $this->uploadWPPHoto($data["photo3"], $slug . "." . $extension);
                    $photo = new Photo();
                    $photo->setProject($project);
                    $photo->setSlug($slug);
                    $photo->setExtension($extension);
                    $photo->setCreatedAt(new \DateTime());
                    $photo->setPosition(2);
                    $photo->setSelected(true);
                    $this->em->persist($photo);
                    $this->em->flush();
                }
            }
        }
    }

    public function uploadWPPHoto($url, $img) {
        $ch = curl_init($url);
        $fp = fopen($this->getParameter('uploadphoto_directory_root') . "/" . $img, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    public function getContact($organization) {
        $contacts = $organization->getContacts();
        if ($contacts && count($contacts) > 1) {
            foreach ($contacts as $c) {
                if ($c->getId() !== $organization->getRepresentative()->getId()) {
                    return $c;
                }
            }
        } elseif ($contacts && count($contacts) == 1) {
            return $contacts[0];
        }
    }

    public function nameCompare($name) {
        $name = trim($name);
        $name = strtoupper($name);
        $name = $this->unaccent($name);
        $name = str_replace(" ", "", $name);
        return $name;
    }

    public function nameIdentique($name, $name2) {
        return $this->nameCompare($name) == $this->nameCompare($name2);
    }

    public function formatAdress($d, $type) {

        $address = $d["association_" . $type . "_addstreet"] != "" ? $d["association_" . $type . "_addstreet"] : null;
        if ($address) {
            $num = ($d["association_" . $type . "_addnum"] && $d["association_" . $type . "_addnum"] != "") ? " " . $d["association_" . $type . "_addnum"] : null;
            if ($num && $num != "-") {
                if ($d["country" . $type] == "BE" || $d["country" . $type] == "LU") {
                    $address = $address . ' ' . $num;
                } else {
                    $address = $num . " " . $address;
                }
            }
        }
        return $address;
    }

    public function cleanName($nom) {
        $nom = mb_strtoupper($nom);
        $nom = str_replace(" ASBL", "", $nom);
        $nom = str_replace("ASBL ", "", $nom);
        $nom = str_replace("ASBL", "", $nom);
        return trim($nom);
    }

    public function cleanAndMaj($text) {
        return trim(mb_strtoupper($text));
    }

    public function cleanUrl($url) {
        $url = strtolower(trim($url));
        $test = explode("//", $url);
        if (count($test) > 1) {
            $url = $url[1];
        }
        $url = "https:/" . "/" . $url;
        return $url;
    }

    public function unaccent($str) {
        $transliteration = array(
            'Ĳ' => 'I', 'Ö' => 'O', 'Œ' => 'O', 'Ü' => 'U', 'ä' => 'a', 'æ' => 'a',
            'ĳ' => 'i', 'ö' => 'o', 'œ' => 'o', 'ü' => 'u', 'ß' => 's', 'ſ' => 's',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Ç' => 'C', 'Ć' => 'C',
            'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'È' => 'E',
            'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ě' => 'E',
            'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
            'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĵ' => 'J',
            'Ķ' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ł' => 'L',
            'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O',
            'Ŏ' => 'O', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Ş' => 'S',
            'Ŝ' => 'S', 'Ș' => 'S', 'Š' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T',
            'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ū' => 'U', 'Ů' => 'U',
            'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ŷ' => 'Y',
            'Ÿ' => 'Y', 'Ý' => 'Y', 'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z', 'à' => 'a',
            'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a',
            'å' => 'a', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c',
            'ď' => 'd', 'đ' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f',
            'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i',
            'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k',
            'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n',
            'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o',
            'ŏ' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'ś' => 's', 'š' => 's',
            'ť' => 't', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ū' => 'u', 'ů' => 'u',
            'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ÿ' => 'y',
            'ý' => 'y', 'ŷ' => 'y', 'ż' => 'z', 'ź' => 'z', 'ž' => 'z', 'Α' => 'A',
            'Ά' => 'A', 'Ἀ' => 'A', 'Ἁ' => 'A', 'Ἂ' => 'A', 'Ἃ' => 'A', 'Ἄ' => 'A',
            'Ἅ' => 'A', 'Ἆ' => 'A', 'Ἇ' => 'A', 'ᾈ' => 'A', 'ᾉ' => 'A', 'ᾊ' => 'A',
            'ᾋ' => 'A', 'ᾌ' => 'A', 'ᾍ' => 'A', 'ᾎ' => 'A', 'ᾏ' => 'A', 'Ᾰ' => 'A',
            'Ᾱ' => 'A', 'Ὰ' => 'A', 'ᾼ' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D',
            'Ε' => 'E', 'Έ' => 'E', 'Ἐ' => 'E', 'Ἑ' => 'E', 'Ἒ' => 'E', 'Ἓ' => 'E',
            'Ἔ' => 'E', 'Ἕ' => 'E', 'Ὲ' => 'E', 'Ζ' => 'Z', 'Η' => 'I', 'Ή' => 'I',
            'Ἠ' => 'I', 'Ἡ' => 'I', 'Ἢ' => 'I', 'Ἣ' => 'I', 'Ἤ' => 'I', 'Ἥ' => 'I',
            'Ἦ' => 'I', 'Ἧ' => 'I', 'ᾘ' => 'I', 'ᾙ' => 'I', 'ᾚ' => 'I', 'ᾛ' => 'I',
            'ᾜ' => 'I', 'ᾝ' => 'I', 'ᾞ' => 'I', 'ᾟ' => 'I', 'Ὴ' => 'I', 'ῌ' => 'I',
            'Θ' => 'T', 'Ι' => 'I', 'Ί' => 'I', 'Ϊ' => 'I', 'Ἰ' => 'I', 'Ἱ' => 'I',
            'Ἲ' => 'I', 'Ἳ' => 'I', 'Ἴ' => 'I', 'Ἵ' => 'I', 'Ἶ' => 'I', 'Ἷ' => 'I',
            'Ῐ' => 'I', 'Ῑ' => 'I', 'Ὶ' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M',
            'Ν' => 'N', 'Ξ' => 'K', 'Ο' => 'O', 'Ό' => 'O', 'Ὀ' => 'O', 'Ὁ' => 'O',
            'Ὂ' => 'O', 'Ὃ' => 'O', 'Ὄ' => 'O', 'Ὅ' => 'O', 'Ὸ' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Ῥ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Ύ' => 'Y',
            'Ϋ' => 'Y', 'Ὑ' => 'Y', 'Ὓ' => 'Y', 'Ὕ' => 'Y', 'Ὗ' => 'Y', 'Ῠ' => 'Y',
            'Ῡ' => 'Y', 'Ὺ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'P', 'Ω' => 'O',
            'Ώ' => 'O', 'Ὠ' => 'O', 'Ὡ' => 'O', 'Ὢ' => 'O', 'Ὣ' => 'O', 'Ὤ' => 'O',
            'Ὥ' => 'O', 'Ὦ' => 'O', 'Ὧ' => 'O', 'ᾨ' => 'O', 'ᾩ' => 'O', 'ᾪ' => 'O',
            'ᾫ' => 'O', 'ᾬ' => 'O', 'ᾭ' => 'O', 'ᾮ' => 'O', 'ᾯ' => 'O', 'Ὼ' => 'O',
            'ῼ' => 'O', 'α' => 'a', 'ά' => 'a', 'ἀ' => 'a', 'ἁ' => 'a', 'ἂ' => 'a',
            'ἃ' => 'a', 'ἄ' => 'a', 'ἅ' => 'a', 'ἆ' => 'a', 'ἇ' => 'a', 'ᾀ' => 'a',
            'ᾁ' => 'a', 'ᾂ' => 'a', 'ᾃ' => 'a', 'ᾄ' => 'a', 'ᾅ' => 'a', 'ᾆ' => 'a',
            'ᾇ' => 'a', 'ὰ' => 'a', 'ᾰ' => 'a', 'ᾱ' => 'a', 'ᾲ' => 'a', 'ᾳ' => 'a',
            'ᾴ' => 'a', 'ᾶ' => 'a', 'ᾷ' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd',
            'ε' => 'e', 'έ' => 'e', 'ἐ' => 'e', 'ἑ' => 'e', 'ἒ' => 'e', 'ἓ' => 'e',
            'ἔ' => 'e', 'ἕ' => 'e', 'ὲ' => 'e', 'ζ' => 'z', 'η' => 'i', 'ή' => 'i',
            'ἠ' => 'i', 'ἡ' => 'i', 'ἢ' => 'i', 'ἣ' => 'i', 'ἤ' => 'i', 'ἥ' => 'i',
            'ἦ' => 'i', 'ἧ' => 'i', 'ᾐ' => 'i', 'ᾑ' => 'i', 'ᾒ' => 'i', 'ᾓ' => 'i',
            'ᾔ' => 'i', 'ᾕ' => 'i', 'ᾖ' => 'i', 'ᾗ' => 'i', 'ὴ' => 'i', 'ῂ' => 'i',
            'ῃ' => 'i', 'ῄ' => 'i', 'ῆ' => 'i', 'ῇ' => 'i', 'θ' => 't', 'ι' => 'i',
            'ί' => 'i', 'ϊ' => 'i', 'ΐ' => 'i', 'ἰ' => 'i', 'ἱ' => 'i', 'ἲ' => 'i',
            'ἳ' => 'i', 'ἴ' => 'i', 'ἵ' => 'i', 'ἶ' => 'i', 'ἷ' => 'i', 'ὶ' => 'i',
            'ῐ' => 'i', 'ῑ' => 'i', 'ῒ' => 'i', 'ῖ' => 'i', 'ῗ' => 'i', 'κ' => 'k',
            'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'k', 'ο' => 'o', 'ό' => 'o',
            'ὀ' => 'o', 'ὁ' => 'o', 'ὂ' => 'o', 'ὃ' => 'o', 'ὄ' => 'o', 'ὅ' => 'o',
            'ὸ' => 'o', 'π' => 'p', 'ρ' => 'r', 'ῤ' => 'r', 'ῥ' => 'r', 'σ' => 's',
            'ς' => 's', 'τ' => 't', 'υ' => 'y', 'ύ' => 'y', 'ϋ' => 'y', 'ΰ' => 'y',
            'ὐ' => 'y', 'ὑ' => 'y', 'ὒ' => 'y', 'ὓ' => 'y', 'ὔ' => 'y', 'ὕ' => 'y',
            'ὖ' => 'y', 'ὗ' => 'y', 'ὺ' => 'y', 'ῠ' => 'y', 'ῡ' => 'y', 'ῢ' => 'y',
            'ῦ' => 'y', 'ῧ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'p', 'ω' => 'o',
            'ώ' => 'o', 'ὠ' => 'o', 'ὡ' => 'o', 'ὢ' => 'o', 'ὣ' => 'o', 'ὤ' => 'o',
            'ὥ' => 'o', 'ὦ' => 'o', 'ὧ' => 'o', 'ᾠ' => 'o', 'ᾡ' => 'o', 'ᾢ' => 'o',
            'ᾣ' => 'o', 'ᾤ' => 'o', 'ᾥ' => 'o', 'ᾦ' => 'o', 'ᾧ' => 'o', 'ὼ' => 'o',
            'ῲ' => 'o', 'ῳ' => 'o', 'ῴ' => 'o', 'ῶ' => 'o', 'ῷ' => 'o', 'А' => 'A',
            'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'E',
            'Ж' => 'Z', 'З' => 'Z', 'И' => 'I', 'Й' => 'I', 'К' => 'K', 'Л' => 'L',
            'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S',
            'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'K', 'Ц' => 'T', 'Ч' => 'C',
            'Ш' => 'S', 'Щ' => 'S', 'Ы' => 'Y', 'Э' => 'E', 'Ю' => 'Y', 'Я' => 'Y',
            'а' => 'A', 'б' => 'B', 'в' => 'V', 'г' => 'G', 'д' => 'D', 'е' => 'E',
            'ё' => 'E', 'ж' => 'Z', 'з' => 'Z', 'и' => 'I', 'й' => 'I', 'к' => 'K',
            'л' => 'L', 'м' => 'M', 'н' => 'N', 'о' => 'O', 'п' => 'P', 'р' => 'R',
            'с' => 'S', 'т' => 'T', 'у' => 'U', 'ф' => 'F', 'х' => 'K', 'ц' => 'T',
            'ч' => 'C', 'ш' => 'S', 'щ' => 'S', 'ы' => 'Y', 'э' => 'E', 'ю' => 'Y',
            'я' => 'Y', 'ð' => 'd', 'Ð' => 'D', 'þ' => 't', 'Þ' => 'T', 'ა' => 'a',
            'ბ' => 'b', 'გ' => 'g', 'დ' => 'd', 'ე' => 'e', 'ვ' => 'v', 'ზ' => 'z',
            'თ' => 't', 'ი' => 'i', 'კ' => 'k', 'ლ' => 'l', 'მ' => 'm', 'ნ' => 'n',
            'ო' => 'o', 'პ' => 'p', 'ჟ' => 'z', 'რ' => 'r', 'ს' => 's', 'ტ' => 't',
            'უ' => 'u', 'ფ' => 'p', 'ქ' => 'k', 'ღ' => 'g', 'ყ' => 'q', 'შ' => 's',
            'ჩ' => 'c', 'ც' => 't', 'ძ' => 'd', 'წ' => 't', 'ჭ' => 'c', 'ხ' => 'k',
            'ჯ' => 'j', 'ჰ' => 'h'
        );
        $str = str_replace(array_keys($transliteration),
                array_values($transliteration),
                $str);
        return $str;
    }

    public function generateDataAllocatedImport($project) {
        $dataJson = array();
        $dataJson["project"] = array(
            "number" => $project->getNumber(),
            "name" => $project->getName()
        );
        if ($project->getOrganization()) {
            $dataJson["representant"] = array(
                "gender" => $project->getOrganization()->getRepresentative()->getGender(),
                "lastname" => $project->getOrganization()->getRepresentative()->getLastname(),
                "firstname" => $project->getOrganization()->getRepresentative()->getFirstname(),
                "position" => $project->getOrganization()->getRepresentative()->getPosition()
            );
            $dataJson["organization"] = array(
                "name" => $project->getOrganization()->getName(),
                "address" => $project->getOrganization()->getOfficeAddress(),
                "zipcode" => $project->getOrganization()->getOfficeZipcode(),
                "city" => $project->getOrganization()->getOfficeCity(),
                "country" => $project->getOrganization()->getOfficeCountry()->getName(),
                "postalbox" => $project->getOrganization()->getOfficePostalbox()
            );
        }
        $president = $this->getPresident();
        if ($president) {
            $dataJson["president"] = array(
                "lastname" => $president->getLastname(),
                "firstname" => $president->getFirstname(),
                "position" => $president->getPosition(),
                "sign" => $president->getSign()->getUrl(),
            );
        }
    }

}

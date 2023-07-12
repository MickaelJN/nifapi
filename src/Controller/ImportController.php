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

class ImportController extends AbstractController {

    use ControllerTrait;

    //rapport final
    //fond

    /**
     * @Route("/importOrganizations", name="importOrganizations", methods={"GET"})
     */
    public function importOrganizations() {

        set_time_limit(0);
        @ini_set("memory_limit", -1);

        $this->importAdmin();
        $this->importOrganization();

        return $this->json([], 200);
    }

    /**
     * @Route("/importProjects", name="importProjects", methods={"GET"})
     */
    public function importProjects() {

        set_time_limit(0);
        @ini_set("memory_limit", -1);
        $html = "";

        //$this->importProject($html);
        //$this->importLocal();
        //$this->importRib();
        //$this->importTransfer();
        //$this->importPayment();
        //$this->importExtension();
        //$this->importInvoice();
        //$this->importRefund();
		
        //$this->calculPercentageReserve();
        //$this->importAnnexeRapport();
        //$this->importAnnexeStatus();
        //$this->importAnnexeComptabilite();
        //$this->importAnnexeOther();
        //$this->importReportFinal();
        //$this->importDemande();
        $this->importLog();

        //return new Response($html);
        return $this->json([], 200);
    }

    public function importAdmin() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/admins.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            if ($d["user_status"] != 1 && $d["user_status"] != 7 && $d["user_active"] == 1) {
                $user = new User();
                $user->setEmail(trim(mb_strtolower($d["user_email"])));
                $user->setFirstname(trim($d["user_firstname"]));
                $user->setLastname(trim(mb_strtoupper($d["user_lastname"])));
                $user->setType("administrateur");
                $user->setPosition(trim($d["user_fonction"]));
                $user->setPhone(trim($d["user_phone"]));
                $user->setMobile(trim($d["user_mobile"]));
                $user->setIsAdmin(false);
                $user->setIsSecretariat($d["user_status"] == 5);
                $user->setIsSecretariatSupport($d["user_status"] == 6);
                $user->setIsPresident(false);
                $user->setGender(2);
                $user->setIsActive(true);
                $user->setDefaultManager(false);
                $user->setOldId($d["user_id"]);

                $user->setPassword($this->userPasswordHasher->hashPassword($user, "Ket712500@N"));

                $this->em->persist($user);
            }
        }
        $this->em->flush();
    }

    public function importOrganization() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/organizations.php');
        $data = json_decode($json, true);

        $cpt = 0;

        foreach ($data as $d) {
            if ($d["user_email"]) {
                $cpt++;
                $organization = new Organization();
                if (array_key_exists("association_denomination", $d)) {
                    $organization->setName($d["association_denomination"] != "" ? $this->cleanName($d["association_denomination"]) : null);
                }
                if (array_key_exists("association_raisonsociale", $d)) {
                    $organization->setLegalStatus(($d["association_raisonsociale"] != "" && $d["association_raisonsociale"] != "/") ? $this->cleanAndMaj($d["association_raisonsociale"]) : null);
                }
                if (array_key_exists("association_abbreviation", $d)) {
                    $organization->setAcronym(($d["association_abbreviation"] != "" && $d["association_abbreviation"] != "/") ? $this->cleanAndMaj($d["association_abbreviation"]) : null);
                }
                if (array_key_exists("association_identification", $d)) {
                    $organization->setIdentificationNumber(($d["association_identification"] != "" && $d["association_identification"] != "/") ? $d["association_identification"] : null);
                }
                if (array_key_exists("association_web", $d)) {
                    $organization->setWebsite(($d["association_web"] != "" && $d["association_web"] != "/") ? $this->cleanUrl($d["association_web"]) : null);
                }
                if (array_key_exists("association_constitution", $d) && $d["association_constitution"] && $d["association_constitution"] != "") {
                    $organization->setDateOfEstablishment(new \DateTime($d["association_constitution"]));
                }
                if (array_key_exists("association_parution", $d) && $d["association_parution"] && $d["association_parution"] != "") {
                    $organization->setDateOfPublication(new \DateTime($d["association_parution"]));
                }
                if (array_key_exists("association_ss_addphone", $d)) {
                    $organization->setPhone(($d["association_ss_addphone"] != "" && $d["association_ss_addphone"] != "/") ? $d["association_ss_addphone"] : null);
                }
                if (array_key_exists("association_ss_addstreet", $d) && $d["association_ss_addstreet"] != "/") {
                    $organization->setHeadquarterAddress($this->formatAdress($d, "ss"));
                }
                if (array_key_exists("association_ss_addzc", $d) && $d["association_ss_addzc"] != "/") {
                    $organization->getHeadquarterZipcode($d["association_ss_addzc"] != "" ? $d["association_ss_addzc"] : null);
                }
                if (array_key_exists("association_ss_addcity", $d) && $d["association_ss_addcity"] != "/") {
                    $organization->setHeadquarterCity($d["association_ss_addcity"] != "" ? $d["association_ss_addcity"] : null);
                }
                if (array_key_exists("countryss", $d)) {
                    $country = $this->countryRepository->findOneBy(array("isocode2" => $d["countryss"]));
                    $organization->setHeadquarterCountry($country);
                }
                if (array_key_exists("association_ss_addbp", $d) && $d["association_ss_addbp"] != "/") {
                    $organization->setHeadquarterPostalbox($d["association_ss_addbp"] != "" ? $d["association_ss_addbp"] : null);
                }
                if (array_key_exists("association_ex_addstreet", $d) && $d["association_ex_addstreet"] != "/") {
                    $organization->setOfficeAddress($this->formatAdress($d, "ex"));
                }
                if (array_key_exists("association_ex_addzc", $d) && $d["association_ex_addzc"] != "/") {
                    $organization->setOfficeZipcode($d["association_ex_addzc"] != "" ? $d["association_ex_addzc"] : null);
                }
                if (array_key_exists("association_ex_addcity", $d) && $d["association_ex_addcity"] != "/") {
                    $organization->setOfficeCity($d["association_ex_addcity"] != "" ? $d["association_ex_addcity"] : null);
                }
                if (array_key_exists("countryex", $d)) {
                    $country = $this->countryRepository->findOneBy(array("isocode2" => $d["countryex"]));
                    $organization->setOfficeCountry($country);
                }
                if (array_key_exists("association_ex_addbp", $d) && $d["association_ex_addbp"] != "/") {
                    $organization->setOfficePostalbox($d["association_ex_addbp"] != "" ? $d["association_ex_addbp"] : null);
                }
                if (array_key_exists("association_denomination", $d)) {
                    $organization->setOldId($d["association_id"]);
                }

                $this->em->persist($organization);

                //génération de la personne de contact
                $user = new User();
                $user->setEmail(trim(strtolower($d["user_email"])));
                $user->setFirstname(trim($d["user_firstname"]));
                $user->setLastname(trim(mb_strtoupper($d["user_lastname"])));
                $user->setType("association");
                $user->setPosition(trim($d["user_fonction"]));
                $user->setPhone(trim($d["user_phone"]));
                $user->setMobile(trim($d["user_mobile"]));
                $user->setIsAdmin(false);
                $user->setIsSecretariat(false);
                $user->setIsSecretariatSupport(false);
                $user->setIsPresident(false);

                $firstnames = array("Patrick", "Pascal", "Tony", "Arnauld", "Paul", "Guillaume", "Jean-Jacques", "Vincent", "Roger", "Matthieu", "Makido", "Marc", "Yannick", "Koen", "Jean-Philippe", "Benoit", "Quentin", "Thibaut", "Christophe", "Bernard", "Denis", "Thibault", "Luc", "Christian", "Sylvio", "Martin", "Marc", "Joël", "Fabian", "Mathieu", "François", "Jean-François", "Claude", "Jean-François", "Ndongo", "Alexandre", "Roberto", "Jean-Vincent", "Alain", "Amaury", "Bernard", "Christian", "Dnalor", "François-Xavier", "Georges", "Jacques", "Jean-Marie", "Jean-Noël", "Jean-Vincent", "Jil", "Joël", "Marc", "Martin", "Ndongo", "Noureddine", "Quentin", "Robert", "Roger", "Samuei");
                $gender = in_array($d["user_firstname"], $firstnames) ? 1 : 2;
                $user->setGender($gender);
                $user->setIsActive($d["user_active"] == 1);
                $user->setDefaultManager(false);
                $user->setOrganization($organization);
                $user->setPassword($this->userPasswordHasher->hashPassword($user, "Ket712500@N"));
                $user->setOldId($d["user_id"]);
                $this->em->persist($user);

                $nomContact = $this->nameCompare($d["user_lastname"] . substr($d["user_firstname"], 0, 1));
                $nomRepresentative = $this->nameCompare($d["association_l_nom"] . substr($d["association_l_prenom"], 0, 1));
                if ($d["association_l_same"] != 1 && $d["association_l_email"] != $d["user_email"] && $d["association_l_nom"] != "" && $nomContact != $nomRepresentative) {
                    $representative = new User();
                    $representative->setEmail(($d["association_l_email"] != "") ? trim(strtolower($d["association_l_email"])) : "mail" . $cpt . "@fakeemail.com");
                    $representative->setFirstname(trim($d["association_l_prenom"]));
                    $representative->setLastname(trim(mb_strtoupper($d["association_l_nom"])));
                    $representative->setType("association");
                    $representative->setPosition(trim($d["association_l_fonction"]));
                    $representative->setPhone(null);
                    $representative->setMobile(null);
                    $representative->setIsAdmin(false);
                    $representative->setIsSecretariat(false);
                    $representative->setIsSecretariatSupport(false);
                    $representative->setIsPresident(false);

                    $firstnames = array("Patrick", "Pascal", "Tony", "Arnauld", "Paul", "Guillaume", "Jean-Jacques", "Vincent", "Roger", "Matthieu", "Makido", "Marc", "Yannick", "Koen", "Jean-Philippe", "Benoit", "Quentin", "Thibaut", "Christophe", "Bernard", "Denis", "Thibault", "Luc", "Christian", "Sylvio", "Martin", "Marc", "Joël", "Fabian", "Mathieu", "François", "Jean-François", "Claude", "Jean-François", "Ndongo", "Alexandre", "Roberto", "Jean-Vincent", "Alain", "Amaury", "Bernard", "Christian", "Dnalor", "François-Xavier", "Georges", "Jacques", "Jean-Marie", "Jean-Noël", "Jean-Vincent", "Jil", "Joël", "Marc", "Martin", "Ndongo", "Noureddine", "Quentin", "Robert", "Roger", "Samuei", "");
                    $gender = in_array($d["association_l_prenom"], $firstnames) ? 1 : 2;
                    $representative->setGender($gender);
                    $representative->setIsActive(true);
                    $representative->setDefaultManager(false);
                    $representative->setOrganization($organization);
                    $representative->setPassword($this->userPasswordHasher->hashPassword($representative, "Ket712500@N"));
                    $this->em->persist($representative);
                    $organization->setRepresentative($representative);
                } else {
                    $organization->setRepresentative($user);
                }

                $this->em->persist($organization);
                $this->em->flush();
            }
        }
    }

    public function importProject(&$html) {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/projects.php');
        $data = json_decode($json, true);
        $defaultManager = $this->getDefaultManager();

        foreach ($data as $d) {
            $project = new Project();
            $project->setNumber($d["pro_num"] != 0 ? (int) $d["pro_num"] : null);
            $organization = $this->organizationRepository->findOneBy(array("oldId" => (int) $d["pro_association"]));
            if (!$organization) {
                $html .= "PROJECT SANS ASSOCIATION => " . $d["pro_name"] . "<br>";
            }
            $project->setOrganization($organization);
            $project->setOldId($d["pro_id"]);
            $project->setName($d["pro_name"]);
            $secteur = $this->secteurRepository->find($d["pro_secteur"]);
            $project->setSecteur($secteur);
            $dateBegin = new \DateTime($d["pro_datedebut"]);
            $project->setDateBegin($dateBegin);
            $dateEnd = new \DateTime($d["pro_datefin"]);
            $dateEnd->modify('last day of this month');
            $project->setDateEnd($dateEnd);
            $manager = $this->userRepository->findOneBy(array("oldId" => $d["pro_suivi"]));
            $project->setManager($manager ? $manager : $defaultManager);
            $project->setMessageManagerNew(0);
            $project->setMessageContactNew(0);
            $paymentType = null;
            if ($d["pro_typepaiement"] == 1) {
                $paymentType = "invoice";
            } elseif ($d["pro_typepaiement"] == 2) {
                $paymentType = "timeline";
            }
            $project->setPaymentType($paymentType);
            if ($paymentType == "invoice") {
                $project->setPercentage($d["pro_pourcentageintervention"]);
            }

            $localisation = ($d["pro_localisation"] && trim($d["pro_localisation"]) != "") ? trim($d["pro_localisation"]) : null;
            $project->setLocation($localisation);

            $paymentTerms = ($d["pro_texteinfobancaire"] && trim($d["pro_texteinfobancaire"]) != "") ? trim($d["pro_texteinfobancaire"]) : null;
            $project->setPaymentTerms($paymentTerms);

            $project->setStatus($this->getProjetStatus($d["pro_status"], $d["pro_typepaiement"]));

            $phase1 = new Phase();
            $phase1->setDescription($d["pro_ph1_description"]);
            $phase1->setCause($d["pro_ph1_raison"]);
            $phase1->setObjectif($d["pro_ph1_objectif1"]);
            $phase1->setObjectif2($d["pro_ph1_objectif2"]);
            $phase1->setResources($d["pro_ph1_moyen"]);
            $phase1->setBeneficiary($d["pro_ph1_beneficiaire"]);
            $phase1->setCost($d["pro_ph1_cout"]);
            $phase1->setFunding($d["pro_ph1_financement"]);
            $phase1->setSolicitation($d["pro_ph1_solicitation"]);
            $phase1->setComment($d["pro_ph1_commentaire"]);
            $phase1->setDuration($d["pro_ph1_duree"]);
            $phase1->setCommentNif(null);
            $phase1->setLocation($d["pro_ph1_lieu"]);
            $this->em->persist($phase1);
            $project->setPhase1($phase1);

            $phase2 = new Phase();
            $phase2->setDescription($d["pro_ph2_description"]);
            $phase2->setCause($d["pro_ph2_raison"]);
            $phase2->setObjectif($d["pro_ph2_objectif1"]);
            $phase2->setObjectif2($d["pro_ph2_objectif2"]);
            $phase2->setResources($d["pro_ph2_moyen"]);
            $phase2->setBeneficiary($d["pro_ph2_beneficiaire"]);
            $phase2->setCost($d["pro_ph2_cout"]);
            $phase2->setFunding($d["pro_ph2_financement"]);
            $phase2->setSolicitation($d["pro_ph2_solicitation"]);
            $phase2->setComment($d["pro_ph2_commentaire"]);
            $phase2->setDuration($d["pro_ph2_duree"]);
            $phase2->setCommentNif(null);
            $phase2->setLocation($d["pro_ph2_lieu"]);
            $this->em->persist($phase2);
            $project->setPhase2($phase2);

            if ($organization) {
                $project->setContact($this->getContact($organization));
                $project->setIsContactValid($project->getContact()->getId() != $organization->getRepresentative()->getId());
            }
            $project->setContactValidationSend(null);
            $project->setContactValidationId(null);

            if ($d["pro_montantalloue"] > 0) {
                $initialAllocated = new AllocatedAmount();
                $initialAllocated->setAmount($d["pro_montantalloue"]);
                if ($d["pro_datevalidation"] != "0000-00-00") {
                    $initialAllocated->setDateAllocated(new \DateTime($d["pro_datevalidation"]));
                }
                $initialAllocated->setReserve($d["pro_montantreserve"]);
                if ($d["pro_datesignature"] != "0000-00-00") {
                    $initialAllocated->setDateSign(new \DateTime($d["pro_datesignature"]));
                } elseif ($d["pro_datevalidation"] != "0000-00-00") {
                    $initialAllocated->setDateSign(new \DateTime($d["pro_datevalidation"]));
                }
                $initialAllocated->setNote($d["pro_president_note"]);
                if ($d["pro_datesignature"] != "0000-00-00") {
                    $initialAllocated->setDateCheck(new \DateTime($d["pro_datesignature"]));
                } elseif ($d["pro_datevalidation"] != "0000-00-00") {
                    $initialAllocated->setDateCheck(new \DateTime($d["pro_datevalidation"]));
                }
                $dataImport = $this->generateDataAllocatedImport($project);
                $initialAllocated->setData($dataImport);
                if ($d["pro_confirmationvalidationpdf"]) {
                    $url = $this->myUtils->generateUniqueFileName();
                    $fileName = $this->getParameter('filename_validation') . $project->getNumber();
                    $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                    $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["pro_confirmationvalidationpdf"], 'r'));
                    $file = new File();
                    $file->setName("Validation allocation");
                    $file->setUrl($url . ".pdf");
                    $file->setExtension("pdf");
                    $file->setType("validation");
                    $file->setSlug($fileName);
                    $initialAllocated->setFile($file);

                    if ($paymentType == "invoice") {
                        $totalAmount = $initialAllocated->getAmount();
                        if ($project->getPercentage() && $project->getPercentage() < 100) {
                            $totalAmount = $initialAllocated->getAmount() / (1 - ((100 - $project->getPercentage()) / 100));
                        }
                        $project->setTotalAmount($totalAmount);
                    }
                } else {
                    $html .= "PROJECT SANS CONFIRMATION ALLOCATION => " . $d["pro_name"] . "<br>";
                }
                $project->setInitialAllocated($initialAllocated);
            }

            $this->em->persist($project);
            $this->em->flush();
        }
        //return new Response($html);
    }

    public function importLocal() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/locals.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $project = $this->projectRepository->findOneBy(array("oldId" => $d["local_pro"]));
            if ($project) {
                $organization = new OrganizationLocal();
                if (array_key_exists("association_denomination", $d) && $d["association_denomination"] != "/") {
                    $organization->setName($d["association_denomination"] != "" ? $this->cleanName($d["association_denomination"]) : null);
                }
                if (array_key_exists("association_raisonsociale", $d) && $d["association_raisonsociale"] != "/") {
                    $organization->setLegalStatus($d["association_raisonsociale"] != "" ? $this->cleanAndMaj($d["association_raisonsociale"]) : null);
                }
                if (array_key_exists("association_abbreviation", $d) && $d["association_abbreviation"] != "/") {
                    $organization->setAcronym($d["association_abbreviation"] != "" ? $this->cleanAndMaj($d["association_abbreviation"]) : null);
                }
                if (array_key_exists("association_identification", $d) && $d["association_identification"] != "/") {
                    $organization->setIdentificationNumber($d["association_identification"] != "" ? $d["association_identification"] : null);
                }
                if (array_key_exists("association_web", $d) && $d["association_web"] != "/") {
                    $organization->setWebsite(($d["association_web"] != "" && $d["association_web"] != "/") ? $this->cleanUrl($d["association_web"]) : null);
                }
                if (array_key_exists("association_constitution", $d) && $d["association_constitution"] && $d["association_constitution"] != "") {
                    $organization->setDateOfEstablishment(new \DateTime($d["association_constitution"]));
                }
                if (array_key_exists("association_parution", $d) && $d["association_parution"] && $d["association_parution"] != "") {
                    $organization->setDateOfPublication(new \DateTime($d["association_parution"]));
                }
                if (array_key_exists("association_ss_addstreet", $d) && $d["association_ss_addstreet"] != "/") {
                    $organization->setHeadquarterAddress($this->formatAdress($d, "ss"));
                }
                if (array_key_exists("association_ss_addzc", $d) && $d["association_ss_addzc"] != "/") {
                    $organization->getHeadquarterZipcode($d["association_ss_addzc"] != "" ? $d["association_ss_addzc"] : null);
                }
                if (array_key_exists("association_ss_addcity", $d) && $d["association_ss_addcity"] != "/") {
                    $organization->setHeadquarterCity($d["association_ss_addcity"] != "" ? $d["association_ss_addcity"] : null);
                }
                if (array_key_exists("countryss", $d)) {
                    $country = $this->countryRepository->findOneBy(array("isocode2" => $d["countryss"]));
                    $organization->setHeadquarterCountry($country);
                }
                if (array_key_exists("association_ss_addbp", $d) && $d["association_ss_addbp"] != "/") {
                    $organization->setHeadquarterPostalbox($d["association_ss_addbp"] != "" ? $d["association_ss_addbp"] : null);
                }
                $organization->setProject($project);
                if ($d["association_locale_q1"] != "") {
                    $project->setLocalAsk1($d["association_locale_q1"]);
                    $project->setLocalAsk2($d["association_locale_q2"]);
                    $project->setLocalAsk3($d["association_locale_q3"]);
                }

                $this->em->persist($organization);
                $this->em->persist($project);
            }
            $this->em->flush();
        }
    }

    public function importTransfer() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/transfers.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $transfer = new Transfer();
            $date = explode("-", $d["vir_mois"]);
            $year = (int) $date[0];
            $month = $date[1];
            $transfer->setMonth($month);
            $transfer->setYear($year);
            $transfer->setAmount($d["vir_montant"]);
            if ($d["vir_execute"] != "0000-00-00") {
                $transfer->setDateExecution(new \DateTime($d["vir_execute"]));
            }
            $transfer->setStatus("executed");
            $this->em->persist($transfer);
        }
        $this->em->flush();
    }

    public function importPayment() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/payments.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $project = $this->projectRepository->findOneBy(array("oldId" => $d["ver_projet"]));
            if ($project) {
                $payment = new Payment();
                $payment->setProject($project);
                $payment->setAmount($d["ver_montant"]);
                $datePayment = null;
                if ($d["ver_date"] != "") {
                    if (strlen($d["ver_date"]) > 7) {
                        $datePayment = new \DateTime($d["ver_date"]);
                    } else {
                        $datePayment = new \DateTime($d["ver_date"] . "-01");
                    }
                    $datePayment->modify('last day of this month')->setTime(23, 59, 59);
                } elseif ($d["ver_isfinal"]) {
                    $datePayment = clone $project->getDateEnd();
                    $datePayment->add(new \DateInterval('P56D'))->modify('last day of this month')->setTime(23, 59, 59);
                }
                $payment->setDatePayment($datePayment);
                $signeDate = null;
                if ($d["ver_signedate"] != "0000-00-00 00:00:00") {
                    $signeDate = new \DateTime($d["ver_signedate"]);
                }
                $payment->setReceiptValidDate($signeDate);
                $payment->setReserve($d["ver_isfinal"] == 1);
                $payment->setReceiptData(null);
                $payment->setOldId($d["ver_id"]);

                if ($d["ver_recu"] != "") {
                    $url = $this->myUtils->generateUniqueFileName();
                    $fileName = "NIF-recu-" . $project->getNumber() . "-" . $datePayment->format("Y") . "-" . $datePayment->format("m");
                    $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                    $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["ver_recu"], 'r'));
                    $file = new File();
                    $file->setName("Reçu paiement " . $datePayment->format("Y") . "-" . $datePayment->format("m"));
                    $file->setUrl($url . ".pdf");
                    $file->setExtension("pdf");
                    $file->setType("receipt");
                    $file->setSlug($fileName);
                    $payment->setReceipt($file);
                }

                $report = null;
                if (array_key_exists("rapport", $d)) {
                    $report = new Report();
                    $report->setStatus("valid");
                    $report->setCreatedAt(new \DateTime($d["rapport"]["rap_add"]));
                    $report->setIsFinal(false);
                    if ($d["rapport"]["rap_rapport"] != "") {

                        $url = $this->myUtils->generateUniqueFileName();
                        $fileName = "Rapport-p" . $project->getNumber() . "-" . $datePayment->format("Y") . "-" . $datePayment->format("m");
                        $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                        $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["rapport"]["rap_rapport"], 'r'));

                        $file = new File();
                        $file->setName("Rapport " . $payment->getDatePayment()->format('Y-m'));
                        $file->setUrl($url . ".pdf");
                        $file->setExtension("pdf");
                        $file->setType("report");
                        $slug = "Rapport_P" . $project->getNumber() . "_" . $payment->getDatePayment()->format('Y-m');
                        $file->setSlug($slug);
                        $this->em->persist($file);
                        $report->setPdf($file);
                    }
                    $this->em->persist($report);
                }
                $payment->setReport($report);

                if ($d["ver_date"] != "" && $d["ver_date"] != "0000-00-00") {
                    $date = explode("-", $d["ver_date"]);
                    $transfer = $this->transferRepository->findOneBy(array("year" => $date[0], "month" => $date[1]));
                    $payment->setTransfer($transfer);
                }

                $this->em->persist($payment);
                $this->em->flush();
            }
        }
        $transfers = $this->transferRepository->findAll();
        foreach ($transfers as $transfer) {
            $name = "NIF_virements_" . $transfer->getYear() . "-" . $transfer->getMonth();
            $this->pdfService->generatePDFTransfer($transfer, $name, $name);
        }
        //$this->em->flush();
        //$payment->setInvoices();
    }

    public function importRib() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/ribs.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $pro = $d["project"];
            $project = $this->projectRepository->findOneBy(array("oldId" => $pro["pro_id"]));
            if ($project) {
                if ($project->getOrganization()) {
                    $organization = $project->getOrganization();
                    if (!$project->getOrganization()->getRib()) {
                        $rib = new Rib();
                        $rib->setIban($pro["pro_banque1"]);
                        $rib->setBic($pro["pro_banque4"]);
                        $rib->setIsSepa($pro["pro_banque5"]);
                        $rib->setBank($pro["pro_banque2"]);
                        $address = null;
                        if ($pro["pro_banque3"] != "") {
                            $address = $pro["pro_banque3"];
                        }
                        if ($pro["pro_banque6"] != "") {
                            $address .= " " . $pro["pro_banque6"];
                        }
                        $rib->setAddress($address);
                        $rib->setNewRib(null);
                        $rib->setIsValid(true);

                        $file = null;
                        if (array_key_exists("rib", $d) && $d["rib"]["files_url"] != "") {

                            $url = "rib-" . $this->myUtils->generateUniqueFileName();
                            $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                            $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["rib"]["files_url"], 'r'));

                            $file = new File();
                            $file->setName("RIB " . $project->getOrganization()->getName());
                            $file->setUrl($url . ".pdf");
                            $file->setExtension("pdf");
                            $file->setType("RIB");
                            $slug = "rib_" . $this->myUtils->slugify($project->getOrganization()->getName());
                            $file->setSlug($slug);
                            $this->em->persist($file);
                        }
                        $rib->setFile($file);

                        $this->em->persist($rib);
                        $organization->setRib($rib);
                        $this->em->persist($organization);
                        $this->em->flush();
                    }
                }
            }
        }
        $ribs = $this->ribRepository->findAll();
        foreach ($ribs as $rib) {
            $code = mb_substr($rib->getIban(), 0, 2);
            $country = $this->countryRepository->findOneByIsocode2($code);
            if ($country) {
                $rib->setCountry($country);
                $rib->setIsSepa($country->getIsSepa());
                $this->em->persist($rib);
                $this->em->flush();
            }
        }
    }

    public function importExtension() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/extensions.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $project = $this->projectRepository->findOneBy(array("oldId" => $d["ver_projet"]));
            if ($project) {
                $extension = new AllocatedAmount();
                $extension->setAmount($d["ver_montant"]);
                $extension->setReserve(0);
                $extension->setDateAllocated(new \DateTime($d["ver_date"]));
                if (array_key_exists("ver_causeextension", $d) && $d["ver_causeextension"] != "") {
                    $extension->setNote($d["ver_causeextension"]);
                } else {
                    $extension->setNote(null);
                }
                $extension->setDateSign(new \DateTime($d["ver_date"]));
                $extension->setDateCheck(null);
                $cause = array_key_exists("ver_causeextension", $d) ? $d["ver_causeextension"] : null;
                $dataJson = array();
                $dataJson["project"] = array(
                    "number" => $project->getNumber(),
                    "name" => $project->getName()
                );
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
                $president = $this->userRepository->findOneBy(array("isPresident" => true));
                if ($president) {
                    $dataJson["president"] = array(
                        "lastname" => $president->getLastname(),
                        "firstname" => $president->getFirstname(),
                        "position" => $president->getPosition(),
                        "sign" => $president->getSign()->getUrl(),
                    );
                }

                if (array_key_exists("cause", $extension->getData())) {
                    $dataJson["cause"] = $extension->getData()["cause"];
                }
                $extension->setData($dataJson);

                if ($d["ver_recu"] != "") {
                    $url = $this->myUtils->generateUniqueFileName();
                    $fileName = $this->getParameter('filename_extension') . $project->getNumber();
                    $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                    $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["ver_recu"], 'r'));
                    $file = new File();
                    $file->setName("Extension allocation");
                    $file->setUrl($url . ".pdf");
                    $file->setExtension("pdf");
                    $file->setType("extension");
                    $file->setSlug($fileName);
                    $extension->setFile($file);
                }

                $extension->setProject($project);
                $this->em->persist($extension);
                $this->em->flush();
            }
        }
    }

    public function importInvoice() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/invoices.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $project = $this->projectRepository->findOneBy(array("oldId" => $d["fac_projet"]));
            if ($project) {
                $invoice = new Invoice();
                $invoice->setSupplier($d["fac_fournisseur"]);
                $invoice->setAmountValid($d["fac_montantmodif"] > 0 ? $d["fac_montantmodif"] : $d["fac_montant"]);
                $invoice->setInitialAmount($d["fac_montant"]);
                $amountToPay = round(($invoice->getAmountValid() * $project->getPercentage() / 100), 2);
                $invoice->setAmountToPay($amountToPay);
                $invoice->setPercentage($project->getPercentage());
                $invoice->setCause($d["fac_cause"]);
                $status = "new";
                if ($d["fac_statut"] == "2") {
                    $status = "updated";
                } elseif ($d["fac_statut"] == "1") {
                    $status = "valid";
                } elseif ($d["fac_statut"] == "3") {
                    $status = "refused";
                }
                $invoice->setStatus($status);
                $invoice->setDateAdd(new \DateTime($d["fac_add"]));
                if ($status != "new") {
                    $invoice->setDateDecision(new \DateTime($d["fac_add"]));
                }
                $invoice->setReserve(0);
                $invoice->setReservePercentage(0);
                $invoice->setCauseAuto(null);
                $invoice->setProject($project);

                if ($d["fac_ispayed"] == 1) {
                    $dateProchainPayment = clone $invoice->getDateDecision();
                    if ($invoice->getDateDecision()->format("d") > 14) {
                        $dateProchainPayment->modify('last day of this month')->add(new \DateInterval('P3D'));
                    }
                    $payment = $this->paymentRepository->nextPaymentByDate($project, $dateProchainPayment);
                    $invoice->setPayment($payment);
                }

                if ($d["fac_justificatif"] != "") {
                    $url = $this->myUtils->generateUniqueFileName();
                    $fileName = $this->getParameter('filename_extension') . $project->getNumber();
                    $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                    $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["fac_justificatif"], 'r'));

                    $file = new File();
                    $file->setName("Facture " . $invoice->getDateAdd()->format("Y-m-d") . " - " . substr($invoice->getSupplier(), 0, 200));
                    $file->setUrl($url . ".pdf");
                    $file->setExtension("pdf");
                    $file->setType("Invoice");
                    $slug = "facture_P" . $project->getNumber();
                    $file->setSlug($slug);
                    $this->em->persist($file);
                    $invoice->setProof($file);
                }
                $this->em->persist($invoice);
                $this->em->flush();
            }
        }
    }

    public function importRefund() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/refunds.php');
        $data = json_decode($json, true);

        $project = $this->projectRepository->findOneBy(array("oldId" => $data[0]["fac_projet"]));
        if ($project) {
            $refund = new Refund();
            $amount = $data[0]["fac_montant"] + $data[1]["fac_montant"];
            $refund->setAmount($amount);
            $refund->setDateAsk(new \DateTime($data[0]["fac_add"]));
            $refund->setDateRefund(new \DateTime($data[0]["fac_add"]));
            $refund->setInitialReserve(0);
            $refund->setAmountToPay($amount);
            $refund->setJustification($data[0]["fac_justificatif"] . " " . $data[1]["fac_justificatif"]);
            $refund->setDateSend(new \DateTime($data[0]["fac_add"]));
            $this->em->persist($refund);

            $project->setRefund($refund);
            $this->em->flush();
        }
    }

    public function calculPercentageReserve() {
        $projects = $this->projectRepository->findBy(array("paymentType" => "invoice", "status" => "in_progress"));

        foreach ($projects as $project) {
            if (!$project->getPercentageReserve()) {
                $total = $project->getTotalAllocated();
                $reserve = $project->getTotalAllocated("reserve");

                if ($reserve == 0) {
                    //calcul de la réserve
                    $reserve = round(($total / 10), 2);
                    $reserve = ($reserve > 2500) ? 2500 : $reserve;
                    if ($reserve > $project->getNotAlreadyPayed()) {
                        $reserve = $project->getNotAlreadyPayed();
                    }
                    $initialAllocated = $project->getInitialAllocated();
                    $initialAllocated->setReserve($reserve);
                    $this->em->persist($initialAllocated);
                }

                if ($reserve <= $project->getNotAlreadyPayed()) {
                    $percentage = $reserve / $project->getNotAlreadyPayed() * 100;
                    $project->setPercentageReserve($percentage);
                } 
				/*else {
                    $html .= $project->getNumber() . " => RESERVE PLUS GRANDE QUE LE RESTE";
                }*/
                $this->em->persist($project);
                $this->em->flush();
            }
        }
    }

    public function importAnnexeRapport() {

        $json = file_get_contents('https://www.fondation-nif.com/projet/export/annexes-rapportannuel.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $organization = $this->organizationRepository->findOneBy(array("oldId" => $d["association"]));
            if ($organization) {

                $url = "annexe_rapport-annuel-" . $this->myUtils->generateUniqueFileName();
                $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["file"]["files_url"], 'r'));

                $file = new File();
                $file->setName("Rapport annuel");
                $file->setUrl($url . ".pdf");
                $file->setExtension("pdf");
                $file->setType("rapportannuel");
                $file->setCreatedAt(new \DateTime($d["file"]["files_add"]));
                $slug = "annexe_rapport-annuel-" . $this->myUtils->slugify($organization->getName());
                $file->setSlug($slug);
                $this->em->persist($file);
                $organization->setAnnexeReport($file);
                $this->em->persist($organization);
                $this->em->flush();
            }
        }
    }

    public function importAnnexeStatus() {

        $json = file_get_contents('https://www.fondation-nif.com/projet/export/annexes-status.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $organization = $this->organizationRepository->findOneBy(array("oldId" => $d["association"]));
            if ($organization) {

                $url = "annexe_status-" . $this->myUtils->generateUniqueFileName();
                $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["file"]["files_url"], 'r'));

                $file = new File();
                $file->setName("Status");
                $file->setUrl($url . ".pdf");
                $file->setExtension("pdf");
                $file->setType("status");
                $file->setCreatedAt(new \DateTime($d["file"]["files_add"]));
                $slug = "annexe_status-" . $this->myUtils->slugify($organization->getName());
                $file->setSlug($slug);
                $this->em->persist($file);
                $organization->setAnnexeStatus($file);
                $this->em->persist($organization);
                $this->em->flush();
            }
        }
    }

    public function importAnnexeComptabilite() {

        $json = file_get_contents('https://www.fondation-nif.com/projet/export/annexes-comptabilite.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $organization = $this->organizationRepository->findOneBy(array("oldId" => $d["association"]));
            if ($organization) {

                $url = "annexe_comptes-annuels" . $this->myUtils->generateUniqueFileName();
                $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["file"]["files_url"], 'r'));

                $file = new File();
                $file->setName("Comptes annuels");
                $file->setUrl($url . ".pdf");
                $file->setExtension("pdf");
                $file->setType("comptabilite");
                $file->setCreatedAt(new \DateTime($d["file"]["files_add"]));
                $slug = "annexe_comptes-annuels-" . $this->myUtils->slugify($organization->getName());
                $file->setSlug($slug);
                $this->em->persist($file);
                $organization->setAnnexeAccount($file);
                $this->em->persist($organization);
                $this->em->flush();
            }
        }
    }

    public function importAnnexeOther() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/annexes-other.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {
            $project = $this->projectRepository->findOneBy(array("oldId" => $d["files_project"]));
            if ($project) {
                try {
                    $url = "annexe_" . $this->myUtils->generateUniqueFileName();
                    $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                    $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["files_url"], 'r'));

                    $file = new File();
                    $file->setName($d["files_name"]);
                    $file->setUrl($url . ".pdf");
                    $file->setExtension("pdf");
                    $file->setType("annexe");
                    $file->setCreatedAt(new \DateTime($d["files_add"]));
                    $slug = "annexe_" . $this->myUtils->slugify($d["files_name"]);
                    $file->setSlug($slug);
                    $file->setProject($project);
                    $this->em->persist($file);
                    $this->em->flush();
                } catch (\Exception $e) {
                    //echo "0";
                }
            }
        }
    }

    public function importReportFinal() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/rapportfinal.php');
        $data = json_decode($json, true);

        $statusRap = ["new", "valid", "refused"];

        foreach ($data as $d) {
            $project = $this->projectRepository->findOneBy(array("oldId" => $d["rap_projet"]));
            if ($project) {
                $report = new Report();

                $report->setStatus($statusRap[$d["rap_valid"]]);
                $report->setCreatedAt(new \DateTime($d["rap_add"]));
                $report->setIsFinal(true);
                if ($d["rap_valid"] == 2) {
                    $report->setRefusDescription($d["rap_cause"]);
                }

                try {
                    $url = $this->myUtils->generateUniqueFileName();
                    $fileName = "Rapportfinal-p" . $project->getNumber();
                    $fileOnServer = $this->getParameter('uploadfile_directory_root') . "/" . $url . ".pdf";
                    $fileDL = file_put_contents($fileOnServer, fopen("https://www.fondation-nif.com/projet/file/" . $d["rap_rapport"], 'r'));

                    $file = new File();
                    $file->setName("Rapport Final");
                    $file->setUrl($url . ".pdf");
                    $file->setExtension("pdf");
                    $file->setType("reportfinal");
                    $slug = "RapportFinal_P" . $project->getNumber();
                    $file->setSlug($slug);
                    $this->em->persist($file);
                    $report->setPdf($file);

                    $this->em->persist($report);
                    $project->setFinalReport($report);
                    $this->em->persist($project);
                    $this->em->flush();
                } catch (\Exception $e) {
                   // echo "0";
                }
            }
        }
    }

    public function importDemande() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/inscriptions.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {

            $old = array(
                "name" => $this->cleanAndMaj($d["demande_denomination"]),
                "legalStatus" => $d["demande_raisonsociale"],
                "acronym" => $d["demande_abbreviation"],
                "identificationNumber" => $d["demande_identification"],
                "dateOfEstablishment" => $d["demande_constitution"],
                "dateOfPublication" => $d["demande_parution"],
                "phone" => $d["demande_ss_addphone"],
                "website" => $d["demande_web"],
                "volunters" => $d["demande_benevole"],
                "employees" => $d["demande_persrenumere"],
                "history" => $d["demande_historique"],
                "activity" => $d["demande_domaine"],
                "headquarterAddress" => trim($d["demande_ss_addstreet"] . " " . $d["demande_ss_addnum"]),
                "headquarterZipcode" => $d["demande_ss_addzc"],
                "headquarterCity" => $d["demande_ss_addcity"],
                "headquarterCountry" => $d["countryss"],
                "representativeEmail" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? $d["demande_c_email"] : "",
                "representativeGender" => '',
                "representativeLastname" => $this->cleanAndMaj($d["demande_l_nom"]),
                "representativeFirstname" => $d["demande_l_prenom"],
                "representativePhone" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? $d["demande_c_telephonef"] : "",
                "representativeMobile" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? $d["demande_c_telephonem"] : "",
                "representativePosition" => $d["demande_l_fonction"],
                "contactIsrepresentative" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]),
                "contactGender" => "",
                "contactLastname" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? "" : $this->cleanAndMaj($d["demande_c_nom"]),
                "contactFirstname" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? "" : $d["demande_c_prenom"],
                "contactPosition" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? "" : $d["demande_c_fonction"],
                "contactPhone" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? "" : $d["demande_c_telephonef"],
                "contactMobile" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? "" : $d["demande_c_telephonem"],
                "contactEmail" => $this->nameIdentique($d["demande_l_nom"] . $d["demande_l_prenom"], $d["demande_c_nom"] . $d["demande_c_prenom"]) ? "" : $d["demande_c_email"],
                "project" => $d["demande_projet"],
                "cg" => true,
                "rgpd" => true
            );

            $subscription = new Subscription();
            $subscription->setData($old);
            $subscription->setCreatedAt(new \DateTime($d["demande_add"]));
            $updated = $d["demande_statut"] == 2 ? new \DateTime($d["demande_upd"]) : null;
            $subscription->setUpdatedAt($updated);
            $subscription->setAlreadyRead(true);
            $subscription->setStatus($d["demande_statut"] == 1 ? "new" : "accepted");
            $this->em->persist($subscription);
            $this->em->flush();
        }
    }

    public function importLog() {
        $json = file_get_contents('https://www.fondation-nif.com/projet/export/logs.php');
        $data = json_decode($json, true);

        foreach ($data as $d) {

            $author = $this->userRepository->findOneBy(array("oldId" => $d["log_author"]));
            $dataOther = array();
            $dataOther["old"] = true;
            if ($author) {
                $dataOther["author"] = array("lastname" => $author->getLastname(), "firstname" => $author->getFirstname());
            }

            if ($d["log_type"] != "project_memo") {


                $action = null;
                if ($d["log_libelle"] == "Ajout d'un rapport final") {
                    $action = "project_add_finalreport";
                } elseif (strpos($d["log_libelle"], "Ajout d'un rapport d'activité") === 0) {
                    $action = "project_add_report";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Refus du rapport d'activité") === 0) {
                    $action = "project_refused_report";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Les virements pour le mois de") !== false && strpos($d["log_libelle"], "ont été exécutés") !== false) {
                    $action = "transfer_status_executed";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif ($d["log_libelle"] == 1) {
                    $action = "project_add";
                } elseif ($d["log_libelle"] == 2) {
                    $action = "project_update_status_phase1_submission";
                } elseif ($d["log_libelle"] == 3) {
                    $action = "project_update_status_phase2_draft";
                } elseif ($d["log_libelle"] == 4) {
                    $action = "project_update_status_phase2_submission";
                } elseif ($d["log_libelle"] == 5) {
                    $action = "project_update_status_in_progress";
                } elseif ($d["log_libelle"] == 6) {
                    $action = "project_update_status_waiting_final_report";
                } elseif ($d["log_libelle"] == 8) {
                    $action = "project_update_status_finished";
                } elseif ($d["log_libelle"] == 9) {
                    $action = "project_update_status_waiting_reserve";
                } elseif ($d["log_libelle"] == 10) {
                    $action = "project_update_status_refusal";
                } elseif ($d["log_libelle"] == "Changement suivi") {
                    $action = "project_update_manager";
                } elseif ($d["log_libelle"] == "Refus du rapport final") {
                    $action = "project_refused_finalreport";
                } elseif ($d["log_libelle"] == "Validation du rapport final") {
                    $action = "project_accepted_finalreport";
                } elseif (strpos($d["log_libelle"], "Validation du rapport d'activité") === 0) {
                    $action = "project_accepted_report";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Création fiche association") === 0) {
                    $action = "organization_add";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Création contact") === 0) {
                    $action = "user_add";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Clôture des versements pour le mois") === 0) {
                    $action = "transfer_status_new";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Versements validés par l'administrateur délégué pour le") === 0) {
                    $action = "transfer_status_valid";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "Le transfert des virements pour le mois") === 0) {
                    $action = "transfer_status_transfer";
                    $data["oldTexte"] = $d["log_libelle"];
                } elseif (strpos($d["log_libelle"], "La signature des bordereaux") === 0) {
                    $action = "transfer_status_waiting_execution";
                    $data["oldTexte"] = $d["log_libelle"];
                }

                
                if ($action) {

                    $logAction = new LogAction();

                    $logAction->setAuthor($author);

                    if ($d["log_type"] == "project") {
                        $logAction = new LogAction();
                        $logAction->setType("project");
                        $project = $this->projectRepository->findOneBy(array("oldId" => $d["log_project"]));
                        $logAction->setProject($project);
                    } elseif ($d["log_type"] == "association") {
                        $logAction->setType("organization");
                        $organization = $this->organizationRepository->findOneBy(array("oldId" => $d["log_association"]));
                        $logAction->setOrganization($organization);
                    } elseif ($d["log_type"] == "user") {
                        $logs["type"] = "user";
                        $user = $this->userRepository->findOneBy(array("oldId" => $d["log_user"]));
                        $logAction->setUser($user);
                    } elseif ($d["log_type"] == "virement") {
                        $logs["type"] = "transfer";
                        if ($d["log_virement"]) {
                            $vir = explode("-", $d["log_virement"]);
                            $transfer = $this->transferRepository->findOneBy(array("year" => $vir[0], "month" => $vir[1]));
                            $logAction->setTransfer($transfer);
                        }
                    }

                    $logAction->setAction($action);
                    $logAction->setData($dataOther);
                    $logAction->setDate(new \DateTime($d["log_add"]));
                    $this->em->persist($logAction);
                    $this->em->flush();

                    $typeMessage = array(
                        "project_update_extension_sign",
                        "project_add_annexe",
                        "project_update_date",
                        "project_update_manager",
                        "project_update_contact",
                        "project_update_refund_confirm",
                        "project_update_validation_sign",
                        "project_add",
                        "project_add_receipt",
                        "project_update_status_phase1_draft_correction",
                        "project_update_status_phase1_draft",
                        "project_update_status_phase1_submission",
                        "project_update_status_phase2_draft_correction",
                        "project_update_status_phase2_draft",
                        "project_update_status_phase2_submission",
                        "project_update_status_in_progress",
                        "project_update_status_waiting_final_report",
                        "project_update_status_waiting_reserve",
                        "project_update_status_finished",
                        "project_refund_ask_refund",
                        "project_waiting_report"
                    );

                    if (in_array($logAction->getAction(), $typeMessage) && $logAction->getProject()) {
                        $message = new Message();
                        $message->setLog($logAction);
                        $message->setDate(new \DateTime($d["log_add"]));
                        $message->setType($logAction->getAction());
                        $message->setContent(null);
                        $message->setProject($logAction->getProject());
                        $message->setData($dataOther);
                        $message->setUser(null);
                        $this->em->persist($message);
                        $this->em->flush();
                    }
                } /*else {
                    echo $d["log_libelle"] . " => " . $d["log_id"] . "<br>";
                }*/
            } else {
                $message = new Message();
                $message->setContent($d["log_libelle"]);
                $project = $this->projectRepository->findOneBy(array("oldId" => $d["log_project"]));
                if ($project) {
                    $message->setProject($project);
                    $message->setType("message");
                    $message->setUser($author);
                    $message->setData($dataOther);
                    $message->setDate(new \DateTime($d["log_add"]));
                    $this->em->persist($message);
                    $this->em->flush();
                }
            }
        }
    }

    public function getProjetStatus($status, $paiment) {
        if ($status == 1) {
            return "phase1_draft";
        }
        if ($status == 2) {
            return "phase1_submission";
        }
        if ($status == 3) {
            return "phase2_draft";
        }
        if ($status == 4) {
            return "phase2_submission";
        }
        if ($status == 5) {
            if ($paiment == 0) {
                return "configuration";
            }
            return "in_progress";
        }
        if ($status == 6) {
            return "waiting_final_report";
        }
        if ($status == 7) {
            return "phase2_submission";
        }
        if ($status == 8) {
            return "finished";
        }
        if ($status == 9) {
            return "waiting_reserve";
        }
        if ($status == 10) {
            return "refusal";
        }
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

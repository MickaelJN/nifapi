<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 *
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Project::class);
    }

    public function add(Project $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return integer Returns Max number project
     */
    public function findMaxNumberProject(): ?string {
        return $this->createQueryBuilder('p')
                        ->select('MAX(p.number)')
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    public function getInitialAllocatedToSign() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.status = :status')
                        ->andWhere('p.initialAllocated IN (SELECT a FROM App\Entity\AllocatedAmount a WHERE a.dateCheck IS NOT null AND a.dateSign IS null AND a.project IS null)')
                        ->setParameter('status', 'configuration')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getInitialAllocatedToCheck() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.status = :status')
                        ->andWhere('p.initialAllocated IN (SELECT a FROM App\Entity\AllocatedAmount a WHERE a.dateCheck IS null AND a.dateSign IS null AND a.project IS null)')
                        ->setParameter('status', 'configuration')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getProjectsWithInvoiceNotValid() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.status = :status')
                        ->andWhere('p IN (SELECT IDENTITY(i.project) FROM App\Entity\Invoice i WHERE i.status = :statusInvoice)')
                        ->setParameter('status', 'in_progress')
                        ->setParameter('statusInvoice', 'new')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getProjectsWithInvoiceNotValidByManager($user) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.status = :status')
                        ->andWhere('p IN (SELECT IDENTITY(i.project) FROM App\Entity\Invoice i WHERE i.status = :statusInvoice)')
                        ->andWhere('p.manager = :user')
                        ->setParameter('status', 'in_progress')
                        ->setParameter('statusInvoice', 'new')
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getProjectsWithReportFinalNotValid() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.status = :status')
                        ->andWhere('p.finalReport IN (SELECT r FROM App\Entity\Report r WHERE r.status = :statusReport)')
                        ->setParameter('status', 'waiting_final_report')
                        ->setParameter('statusReport', 'new')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getProjectsWithReportFinalNotValidByManager($user) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.status = :status')
                        ->andWhere('p.manager = :user')
                        ->andWhere('p.finalReport IN (SELECT r FROM App\Entity\Report r WHERE r.status = :statusReport)')
                        ->setParameter('status', 'waiting_final_report')
                        ->setParameter('statusReport', 'new')
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getNumberNotNull() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.number IS NOT null')
                        ->addOrderBy('p.number', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getProjectWithoutSiteTexte() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('(p.webStatus = :statusDraft OR p.webStatus = :statusPending) AND (p.status != :statusCanceled AND p.status != :statusRefused) AND p.number IS NOT null')
                        ->setParameter('statusDraft', 'draft')
                        ->setParameter('statusPending', 'pending')
                        ->setParameter('statusCanceled', 'canceled')
                        ->setParameter('statusRefused', 'refusal')
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getProjectsWithPhotoOfflineByUser($user) {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p IN (SELECT IDENTITY(i.project) FROM App\Entity\Photo i WHERE i.selected = :selected)');
        if(!$user->getIsAdmin()){
            $qb->andWhere('p.manager = :manager')
               ->setParameter('manager', $user->getId(), 'uuid');
        }
                        return $qb->setParameter('selected', false)
                        ->addOrderBy('p.number', 'ASC')
                        ->addOrderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function search($search, $user) {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.name LIKE :search or Cast(p.number as varchar(5)) LIKE :search')
                ->setParameter('search', '%' . $search . '%');

        if ($user->getType() == "association") {
            $qb->andWhere('p.organization = :organization')
                    ->setParameter('organization', $user->getOrganization()->getId(), 'uuid');
        }

        $qb->addOrderBy('p.number', 'DESC')
                ->addOrderBy('p.id', 'ASC');
        return $qb->getQuery()->getResult();
    }
    
    public function getAllProjectsReal(){
        $qb = $this->createQueryBuilder('p');
        return $qb->where('(p.status != :statusCanceled and p.status != :statusRefused and p.status != :statusConfiguration) AND p.number IS NOT null')
                        ->setParameter('statusCanceled', 'canceled')
                        ->setParameter('statusRefused', 'refusal')
                ->setParameter('statusConfiguration', 'configuration')
                        ->addOrderBy('p.number', 'ASC')
                        ->getQuery()->getResult();
    }
    
    public function getAllProjectLastYear(){
        $year = date("Y") - 1;
        $first = new \DateTime('first day of january '.$year);
        $last = new \DateTime('last day of december '.$year);
        
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.number IS NOT null AND ((p.dateBegin >= :first AND p.dateBegin <= :last) OR (p.dateEnd >= :first AND p.dateEnd <= :last) OR (p.dateBegin <= :first AND p.dateEnd >= :last)) AND (p.status != :statusCanceled and p.status != :statusRefused and p.status != :statusConfiguration)') 
                        ->setParameter('first', $first)
                        ->setParameter('last', $last)
                ->setParameter('statusCanceled', 'canceled')
                        ->setParameter('statusRefused', 'refusal')
                ->setParameter('statusConfiguration', 'configuration')
                        ->addOrderBy('p.number', 'ASC')
                        ->getQuery()->getResult();
    }

//    /**
//     * @return Project[] Returns an array of Project objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?Project
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

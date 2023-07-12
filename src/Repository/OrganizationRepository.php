<?php

namespace App\Repository;

use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 *
 * @method Organization|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organization|null findOneBy(array $criteria, array $orderBy = null)
 * @method Organization[]    findAll()
 * @method Organization[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganizationRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Organization::class);
    }

    public function add(Organization $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Organization $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllRibNotValid() {
        $qb = $this->createQueryBuilder('o');
        return $qb->where('o.rib IN (SELECT r FROM App\Entity\RIB r WHERE r.isValid = 0)')
                        ->orderBy('o.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getAllRibNotValidByManager($user) {
        $qb = $this->createQueryBuilder('o');
        return $qb->where('o.rib IN (SELECT r FROM App\Entity\Rib r WHERE r.isValid = 0)')
                        ->andWhere('o IN (SELECT IDENTITY(p.organization) FROM App\Entity\Project p WHERE p.manager = :user)')
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->orderBy('o.id', 'ASC')
                        ->getQuery()->getResult();
    }
    
    public function search($search, $user) {
        $qb = $this->createQueryBuilder('o');
        $qb->where('o.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');

        if ($user->getType() == "association") {
            $qb->andWhere('o.id = :organization')
                    ->setParameter('organization', $user->getOrganization()->getId(), 'uuid');
        }

        $qb->addOrderBy('o.name', 'ASC');
        
        return $qb->getQuery()->getResult();
    }
    
    public function getAllOrganizationWithRealProject() {
        $qb = $this->createQueryBuilder('o');
        return $qb->where('o IN (SELECT IDENTITY(p.organization) FROM App\Entity\Project p WHERE p.number IS NOT NULL AND p.status != :canceled AND p.status != :refusal)')
                ->setParameter('canceled', 'canceled')
                ->setParameter('refusal', 'refusal')
                        ->orderBy('o.name', 'ASC')
                        ->getQuery()->getResult();
    }
    
    public function getOldStatus(){
        $one_year = new \DateInterval("P1Y");
            $one_year->invert = 1;
            $one_year_ago = new \DateTime();
            $one_year_ago->add($one_year);
            
        $qb = $this->createQueryBuilder('o');
        return $qb->where('(o.annexeStatus IN (SELECT f FROM App\Entity\File f WHERE f.type = :type AND f.organization IS null AND f.createdAt <= :createdAt) OR o.annexeStatus IS null) AND o IN (SELECT IDENTITY(p.organization) FROM App\Entity\Project p WHERE p.number IS NOT null AND p.status != :canceled AND p.status != :refusal AND p.status != :finished)')
                ->setParameter('type', 'status')
                ->setParameter('createdAt', $one_year_ago)
                 ->setParameter('canceled', 'canceled')
                ->setParameter('refusal', 'refusal')
                 ->setParameter('finished', 'finished')
                        ->orderBy('o.name', 'ASC')
                        ->getQuery()->getResult();
    }

//    /**
//     * @return Organization[] Returns an array of Organization objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?Organization
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

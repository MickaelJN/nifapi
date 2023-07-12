<?php

namespace App\Repository;

use App\Entity\AllocatedAmount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AllocatedAmount>
 *
 * @method AllocatedAmount|null find($id, $lockMode = null, $lockVersion = null)
 * @method AllocatedAmount|null findOneBy(array $criteria, array $orderBy = null)
 * @method AllocatedAmount[]    findAll()
 * @method AllocatedAmount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AllocatedAmountRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, AllocatedAmount::class);
    }

    public function add(AllocatedAmount $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AllocatedAmount $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getExtensionsToSign() {
        $qb = $this->createQueryBuilder('e');
        return $qb->where('e.dateCheck IS NOT null')
                        ->andWhere('e.dateSign IS null')
                        ->andWhere('e.project IS NOT null')
                        ->orderBy('e.id', 'ASC')
                        ->getQuery()->getResult();
    }
    
    public function getExtensionsToCheck() {
        $qb = $this->createQueryBuilder('e');
        return $qb->where('e.dateCheck IS null')
                        ->andWhere('e.dateSign IS null')
                        ->andWhere('e.project IS NOT null')
                        ->orderBy('e.id', 'ASC')
                        ->getQuery()->getResult();
    }

//    /**
//     * @return AllocatedAmount[] Returns an array of AllocatedAmount objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?AllocatedAmount
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Message::class);
    }

    public function add(Message $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Message $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLastMessagesByContact($user, $dateLimit) {
        $query = $this->createQueryBuilder("m")
                        ->select("MAX(m.date) as date, p.name, p.number, p.id, p.messageContactNew")
                        ->join("m.project", "p")
                        ->where('p.contact = :user AND (p.messageContactNew > 0 OR m.date > :date)')
                        ->groupBy("p")
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->setParameter('date', $dateLimit)
                        ->addOrderBy('date', 'DESC')
                        ->getQuery()->getResult();
        return $query;
    }

    public function getLastMessagesByRepresentative($user, $dateLimit) {
        $query = $this->createQueryBuilder("m")
                        ->select("MAX(m.date) as date, p.name, p.number, p.id, p.messageContactNew")
                        ->join("m.project", "p")
                        ->join("p.organization", "o")
                        ->where('p.organization = :organization AND (p.messageContactNew > 0 OR m.date > :date)')
                        ->groupBy("p")
                        ->setParameter('organization', $user->getOrganization()->getId(), 'uuid')
                        ->setParameter('date', $dateLimit)
                        ->addOrderBy('date', 'DESC')
                        ->getQuery()->getResult();
        return $query;
    }

    public function getLastMessagesByManager($user, $dateLimit) {
        $query = $this->createQueryBuilder("m")
                        ->select("MAX(m.date) as date, p.name, p.number, p.id, p.messageManagerNew, o.name as organization")
                        ->join("m.project", "p")
                        ->join("p.organization", "o")
                        ->where('p.manager = :user AND (p.messageManagerNew > 0 OR m.date > :date)')
                        ->groupBy("p")
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->setParameter('date', $dateLimit)
                        ->addOrderBy('date', 'DESC')
                        ->getQuery()->getResult();
        return $query;
    }
    
    
    public function getNewMessagesByContact($user) {
        $query = $this->createQueryBuilder("m")
                        ->select("MAX(m.date) as date, p.name, p.number, p.id, p.messageContactNew")
                        ->join("m.project", "p")
                        ->where('p.contact = :user AND p.messageContactNew > 0')
                        ->groupBy("p")
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->getQuery()->getResult();
        return $query;
    }
    
    public function getNewMessagesByManager($user) {
        $query = $this->createQueryBuilder("m")
                        ->select("MAX(m.date) as date, p.name, p.number, p.id, p.messageContactNew")
                        ->join("m.project", "p")
                        ->where('p.manager = :user AND p.messageMangerNew > 0')
                        ->groupBy("p")
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->getQuery()->getResult();
        return $query;
    }
    

//    /**
//     * @return Message[] Returns an array of Message objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?Message
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

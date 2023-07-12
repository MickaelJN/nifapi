<?php

namespace App\Repository;

use App\Entity\LogAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAction>
 *
 * @method LogAction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogAction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogAction[]    findAll()
 * @method LogAction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogActionRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, LogAction::class);
    }

    public function add(LogAction $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LogAction $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAllActions() {
        $qb = $this->createQueryBuilder('l');
        return $qb->select('l.action')
                        ->groupBy('l.action')
                        ->addOrderBy('l.action', 'ASC')
                        ->getQuery()->getResult();
    }

    public function filterLogs($project = null, $organization = null, $user = null, $author = null, $action = null, $limit = null, $offset = null) {
        $qb = $this->createQueryBuilder('l');
        $qb->where('l.action IS NOT null');

        if ($project) {
            $qb->andWhere("l.project = :project")
                    ->setParameter('project', $project, 'uuid');
        }

        if ($organization) {
            $qb->andWhere("(l.organization = :organization OR l.user IN (SELECT u FROM App\Entity\User u WHERE u.type = :type and u.organization = :organization))")
                     ->setParameter('type', 'association')
                    ->setParameter('organization', $organization, 'uuid');
        }

        if ($user) {
            $qb->andWhere("l.user = :user")
                    ->setParameter('user', $user, 'uuid');
        }

        if ($author) {
            $qb->andWhere("l.author = :author")
                    ->setParameter('author', $author, 'uuid');
        }

        if ($action) {
            if ($action === "mostImportant") {
                $qb->andWhere("l.action = :action")
                        ->setParameter('action', $action);
            }
            elseif ($action === "onlyTransfer") {
                $qb->andWhere("l.transfer IS NOT null");
            }
            elseif ($action === "onlyProject") {
                $qb->andWhere("l.project IS NOT null");
            }
            elseif ($action === "onlyOrganization") {
                $qb->andWhere("l.organization IS NOT null");
            }
            elseif ($action === "onlyUser") {
                $qb->andWhere("l.user IS NOT null");
            }
            elseif ($action === "onlyAutomatic") {
                $qb->andWhere("l.author IS null");
            }
            else {
                $qb->andWhere("l.action = :action")
                        ->setParameter('action', $action);
            }
        }

        $qb->addOrderBy('l.date', 'DESC');
        
        if($offset){
            $qb->setFirstResult($offset);            
        }
        if($limit){
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return LogAction[] Returns an array of LogAction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?LogAction
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

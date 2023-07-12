<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }

    public function getUsersWithNewMessage() {
        $query = $this->createQueryBuilder("u")
                        ->select("DISTINCT u.email AS email")
                        ->where('u IN (SELECT IDENTITY(p.manager) FROM App\Entity\Project p WHERE p.messageManagerNew > 0) OR u IN (SELECT IDENTITY(p2.contact) FROM App\Entity\Project p2 WHERE p2.messageContactNew > 0)')
                        ->getQuery()->getResult();
        return $query;
    }

    public function search($search, $user) {
        $qb = $this->createQueryBuilder('u');
        $qb->where('u.lastname LIKE :search or u.firstname LIKE :search or u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');

        if ($user->getType() == "association") {
            $qb->andWhere('u.organization = :organization')
                    ->setParameter('organization', $user->getOrganization()->getId(), 'uuid');
        }

        $qb->addOrderBy('u.lastname', 'DESC');

        return $qb->getQuery()->getResult();
    }
    
    public function getIdentityToCheck(){
        $query = $this->createQueryBuilder("u")
                        ->where('u.type = :type AND u.identityCard IS NOT null AND u.identityCardValid = false')
                ->setParameter('type', 'association')
                ->getQuery()->getResult();
        return $query;
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 *
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Payment::class);
    }

    public function add(Payment $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Payment $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function nextPayment(): ?Payment {
        $qb = $this->createQueryBuilder('p');
        return $qb->where($qb->expr()->isNull("p.transfer"))
                        ->orderBy('p.datePayment', 'ASC')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
    }

    public function nextPaymentByPayment($payment): ?Payment {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.datePayment > :date')
                        ->andWhere('p.id != :id')
                        ->andWhere('p.project = :project')
                        ->setParameter('date', $payment->getDatePayment())
                        ->setParameter('id', $payment->getId(), 'uuid')
                        ->setParameter('project', $payment->getProject()->getId(), 'uuid')
                        ->orderBy('p.datePayment', 'ASC')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
    }

    public function prevPaymentByPayment($payment): ?Payment {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.datePayment < :date')
                        ->andWhere('p.id != :id')
                        ->andWhere('p.project = :project')
                        ->setParameter('date', $payment->getDatePayment())
                        ->setParameter('id', $payment->getId(), 'uuid')
                        ->setParameter('project', $payment->getProject()->getId(), 'uuid')
                        ->orderBy('p.datePayment', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
    }

    public function lastPaymentPayedByProject($project): ?Payment {
        $qb = $this->createQueryBuilder('p');
        return $qb->where($qb->expr()->isNotNull('p.transfer'))
                        ->andWhere($qb->expr()->isNotNull('p.receipt'))
                        ->andWhere('p.project = :project')
                        //->andWhere('p.datePayment < :datePayment')
                        ->setParameter('project', $project->getId(), 'uuid')
                        //->setParameter('datePayment', new \DateTime())
                        ->orderBy('p.datePayment', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
    }

    public function hasSameDate($project, $payment, $date): ?Payment {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.project = :project')
                        ->andWhere('p.id != :payment')
                        ->andWhere('p.datePayment = :datePayment')
                        ->setParameter('project', $project->getId(), 'uuid')
                        ->setParameter('payment', $payment->getId(), 'uuid')
                        ->setParameter('datePayment', $date)
                        ->orderBy('p.datePayment', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
    }

    public function nextPaymentByDate($project, $date): ?Payment {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.project = :project')
                        ->andWhere('p.datePayment >= :datePayment')
                        ->setParameter('project', $project->getId(), 'uuid')
                        ->setParameter('datePayment', $date)
                        ->orderBy('p.datePayment', 'ASC')
                        ->setMaxResults(1)
                        ->getQuery()->getOneOrNullResult();
    }

    public function getPaymentsWithReportNotValid() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.report IN (SELECT r FROM App\Entity\Report r WHERE r.status = :statusReport)')
                        ->setParameter('statusReport', 'new')
                        ->orderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getPaymentsWithReportNotValidByManager($user) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.report IN (SELECT r FROM App\Entity\Report r WHERE r.status = :statusReport)')
                        ->andWhere('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.manager = :user)')
                        ->setParameter('statusReport', 'new')
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->orderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getPaymentsWithReceiptNotValid() {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.receipt IS NOT NULL AND p.receiptValidDate IS NULL')
                        ->orderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getPaymentsWithReceiptNotValidByManager($user) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.receipt IS NOT NULL AND p.receiptValidDate IS NULL')
                        ->andWhere('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.manager = :user)')
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->orderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getPaymentsWithReceiptNotValidByOrganization($organization) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.receipt IS NOT NULL AND p.receiptValidDate IS NULL')
                        ->andWhere('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.organization = :organization)')
                        ->setParameter('organization', $organization->getId(), 'uuid')
                        ->orderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getPaymentsWithReceiptNotValidByContact($user) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.receipt IS NOT NULL AND p.receiptValidDate IS NULL')
                        ->andWhere('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.contact = :user)')
                        ->setParameter('user', $user->getId(), 'uuid')
                        ->orderBy('p.id', 'ASC')
                        ->getQuery()->getResult();
    }

    public function getPaymentByOrganization($organization) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.organization = :organization)')
                        ->setParameter('organization', $organization->getId(), 'uuid')
                        ->orderBy('p.datePayment', 'ASC')
                        ->getQuery()->getResult();
    }
    
    public function getPaymentReserveInPast($lastCurrentMonth) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.isReserve = :isReserve and p.datePayment <= :datePayment and p.transfer IS null')
                        ->setParameter('isReserve', true)
                        ->setParameter('datePayment', $lastCurrentMonth)
                        ->orderBy('p.datePayment', 'ASC')
                        ->getQuery()->getResult();
    }
    
    public function getPaymentReserveWaitingReportInPast($lastCurrentMonth) {
        $qb = $this->createQueryBuilder('p');
        return $qb->where('p.isReserve = :isReserve and p.datePayment <= :datePayment and p.transfer IS null AND p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.status = :status)')
                        ->setParameter('isReserve', true)
                        ->setParameter('datePayment', $lastCurrentMonth)
                        ->setParameter('status', "waiting_final_report")
                        ->orderBy('p.datePayment', 'ASC')
                        ->getQuery()->getResult();
    }

    /* public function getPaymentsWithReportNotSendByOrganization($organization) {
      $qb = $this->createQueryBuilder('p');
      $results =  $qb->where('p.report IS NULL AND p.transfer IN (SELECT t FROM App\Entity\Transfer pr WHERE status = :statusTransfer)')
      ->andWhere('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.organization = :organization AND pr.paymentType = :paymentType)')
      ->andWhere('p.datePayment IN (SELECT pr FROM App\Entity\Project pr WHERE pr.organization = :organization AND pr.paymentType = :paymentType)')
      ->setParameter('statusTransfer', 'executed')
      ->setParameter('organization', $organization->getId(), 'uuid')
      ->setParameter('paymentType', 'timeline')
      ->orderBy('p.id', 'ASC')
      ->getQuery()->getResult();
      $data = array();

      foreach($results as $r){
      $nextPayment = $this->nextPaymentByPayment($r->getId());
      }

      }

      public function getPaymentsWithReportNotSendByContact($user) {
      $qb = $this->createQueryBuilder('p');
      return $qb->where('p.report IN (SELECT r FROM App\Entity\Report r WHERE r.status = :statusReport)')
      ->andWhere('p.project IN (SELECT pr FROM App\Entity\Project pr WHERE pr.manager = :user)')
      ->setParameter('statusReport', 'new')
      ->setParameter('user', $user->getId(), 'uuid')
      ->orderBy('p.id', 'ASC')
      ->getQuery()->getResult();
      } */

//    /**
//     * @return Payment[] Returns an array of Payment objects
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
//    public function findOneBySomeField($value): ?Payment
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

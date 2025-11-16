<?php

namespace App\Repository;

use App\Entity\OrderEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderEntity>
 */
class OrderEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderEntity::class);
    }

    public function createOrder(OrderEntity $order):void
    {
        $this->getEntityManager()->persist($order);

        $this->getEntityManager()->flush();
    }

    public function updateOrder(OrderEntity $order):void
    {
        /*
         * Здесь ещё какая-нибудь обработка
         */

        //  ...

        $this->getEntityManager()->flush();
    }
}

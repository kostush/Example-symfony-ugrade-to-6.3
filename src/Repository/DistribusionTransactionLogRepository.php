<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class DistribusionTransactionLogRepository extends EntityRepository
{
    public function markLogProcessed($transactionId)
    {
        return $this->createQueryBuilder('tl')
            ->update()
            ->set('tl.isProcessed', true)
            ->where('tl.transactionId = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->getQuery()
            ->execute();
    }
}
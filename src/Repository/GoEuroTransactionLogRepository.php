<?php

namespace App\Repository;

use App\Entity\GoEuroTransactionLog;
use Doctrine\ORM\EntityRepository;

class GoEuroTransactionLogRepository extends EntityRepository
{
    public function findAllUnprocessed()
    {
        return $this->createQueryBuilder('tl')
            ->select()
            ->where('tl.isProcessed = :isProcessed')
            ->andWhere('tl.errorAttempts < :maxAttempts')
            ->setParameters([
                'isProcessed' => false,
                'maxAttempts' => GoEuroTransactionLog::MAX_ERROR_ATTEMPTS
            ])->getQuery()->getResult();
    }
}
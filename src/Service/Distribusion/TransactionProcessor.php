<?php

namespace App\Service\Distribusion;

use App\Entity\DistribusionTransactionLog;
use App\Mapping\Distribusion\Transaction;
use App\Repository\DistribusionTransactionLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransactionProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DistribusionTransactionLogRepository
     */
    private $repository;

    /**
     * @var JsonConverter
     */
    private $converter;

    public function __construct(EntityManagerInterface $entityManager, JsonConverter $converter)
    {
        $this->repository = $entityManager->getRepository(DistribusionTransactionLog::class);
        $this->converter = $converter;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Transaction $transaction
     * @throws \Exception
     */
    public function addToLog(Transaction $transaction)
    {
        if (!$this->isInLog($transaction->getTicketNr())) {
            $log = new DistribusionTransactionLog();

            $log
                ->setTransactionId($transaction->getTicketNr())
                ->setTravelInfo($this->converter->getTravelInfo($transaction))
                ->setCreatedAt(new \DateTime());

            $this->entityManager->persist($log);
        }

        $this->entityManager->flush();
    }

    private function isInLog(string $transactionId)
    {
        return $this->repository->findOneBy([
            'transactionId' => $transactionId,
        ]);
    }
}
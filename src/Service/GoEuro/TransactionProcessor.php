<?php

namespace App\Service\GoEuro;

use App\Entity\GoEuroTransactionLog;
use App\Mapping\GoEuro\Transaction;
use App\Repository\GoEuroTransactionLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransactionProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var GoEuroTransactionLogRepository
     */
    private $repository;

    /**
     * @var JsonConverter
     */
    private $converter;

    public function __construct(EntityManagerInterface $entityManager, JsonConverter $converter)
    {
        $this->repository = $entityManager->getRepository(GoEuroTransactionLog::class);
        $this->converter = $converter;
        $this->entityManager = $entityManager;
    }

    /**
     * @param array|Transaction[] $transactions
     * @return int
     * @throws \Exception
     */
    public function addToLog(array $transactions, bool $travelDateStatus): int
    {
        $transactionsImported = 0;

        foreach ($transactions as $transaction) {
            if ($this->importTransaction($transaction, $travelDateStatus)) {
                $transactionsImported++;
            }
        }

        $this->entityManager->flush();

        return $transactionsImported;
    }

    public function addSingleLog(Transaction $transaction): ?GoEuroTransactionLog
    {
        $log = $this->importTransaction($transaction);
        $this->entityManager->flush();

        return $log;
    }

    public function findTransactionById(string $transactionId): ?GoEuroTransactionLog
    {
        return $this->repository->findOneBy([
            'transactionId' => $transactionId,
        ]);
    }

    private function importTransaction(Transaction $transaction, bool $travelDateStatus = false): ?GoEuroTransactionLog
    {
        if (!$this->findTransactionById($transaction->getId())) {
            if ($travelDateStatus && !$this->isActualTravelDate($transaction)) {
                return null;
            }

            $log = new GoEuroTransactionLog();

            $log
                ->setTransactionId($transaction->getId())
                ->setTravelDate($transaction->getTravelDate())
                ->setTransactionJson($this->converter->getTransactionJson($transaction))
                ->setCreatedAt(new \DateTime());

            $this->entityManager->persist($log);

            return $log;
        }

        return null;
    }

    private function isActualTravelDate(Transaction $transaction)
    {
        return $transaction->getTravelDate() > new \DateTime();
    }
}
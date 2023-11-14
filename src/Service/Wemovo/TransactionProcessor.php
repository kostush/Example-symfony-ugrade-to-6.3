<?php

namespace App\Service\Wemovo;

use App\Entity\WemovoTransactionLog;
use App\Mapping\Wemovo\Transaction;
use App\Repository\WemovoTransactionLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransactionProcessor
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var WemovoTransactionLogRepository
     */
    private $repository;

    /**
     * @var JsonConverter
     */
    private $converter;

    public function __construct(EntityManagerInterface $entityManager, JsonConverter $converter)
    {
        $this->repository = $entityManager->getRepository(WemovoTransactionLog::class);
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

    public function addSingleLog(Transaction $transaction): ?WemovoTransactionLog
    {
        $log = $this->importTransaction($transaction);
        $this->entityManager->flush();

        return $log;
    }

    public function findTransactionById(string $transactionId): ?WemovoTransactionLog
    {
        return $this->repository->findOneBy([
            'transactionId' => $transactionId,
        ]);
    }

    private function importTransaction(Transaction $transaction, bool $travelDateStatus = false): ?WemovoTransactionLog
    {
        if (!$this->findTransactionById($transaction->getBookingId())) {
            if ($travelDateStatus && !$this->isActualTravelDate($transaction)) {
                return null;
            }

            $log = new WemovoTransactionLog();

            $log
                ->setTransactionId($transaction->getBookingId())
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
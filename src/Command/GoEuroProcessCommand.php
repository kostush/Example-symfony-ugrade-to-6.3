<?php

namespace App\Command;


use App\Entity\GoEuroTransactionLog;
use App\Repository\GoEuroTransactionLogRepository;
use App\Service\EmailNotifier;
use App\Service\GoEuro\JsonConverter;
use App\Service\GoEuro\TicketProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GoEuroProcessCommand extends Command
{
    protected static $defaultName = 'app:process:go-euro';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var GoEuroTransactionLogRepository
     */
    private $transactionLogRepository;

    /**
     * @var JsonConverter
     */
    private $jsonConverter;

    /**
     * @var TicketProcessor
     */
    private $ticketProcessor;
    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    public function __construct(EntityManagerInterface $entityManager, JsonConverter $jsonConverter, TicketProcessor $ticketProcessor, EmailNotifier $emailNotifier)
    {
        $this->entityManager = $entityManager;
        $this->transactionLogRepository = $entityManager->getRepository(GoEuroTransactionLog::class);
        $this->jsonConverter = $jsonConverter;
        $this->ticketProcessor = $ticketProcessor;
        $this->emailNotifier = $emailNotifier;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Process GoEuro transactions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processErrors = 0;
        /** @var GoEuroTransactionLog[] $transactionLogs */
        $transactionLogs = $this->transactionLogRepository->findAllUnprocessed();

        foreach ($transactionLogs as $transactionLog) {
            $transaction = $this->jsonConverter->convertTransaction($transactionLog->getTransactionJson());

            try {
                $data = $this->ticketProcessor->getTicketData($transaction);
                $transactionLog->setIsProcessed(true)->setLatestResponse(json_encode($data));
            } catch (\Exception $ex) {
                $processErrors++;
                $currentAttempt = $transactionLog->getErrorAttempts() + 1;

                if ($currentAttempt == 1) { // notify on the first unsuccessful attempt
                    try {
                        $this->emailNotifier->notifyAboutError(
                            $ex->getMessage(),
                            $transactionLog->getTransactionJson(),
                            'GoEuro error notification'
                        );
                    } catch (\Exception $emailNotifierException) {
                        $output->writeln($emailNotifierException->getMessage());
                    }
                }

                $this->entityManager->persist(
                    $transactionLog
                        ->setLatestResponse($ex->getMessage())
                        ->setErrorAttempts($currentAttempt)
                );
            }

            $this->entityManager->flush();
        }

        $output->writeln(sprintf('%d transactions processed. Errors: %d', count($transactionLogs), $processErrors));
    }
}
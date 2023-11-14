<?php

namespace App\Command;

use App\Service\GoEuro\ApiProcessor;
use App\Service\GoEuro\JsonConverter;
use App\Service\GoEuro\TransactionProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GoEuroImportCommand extends Command
{
    protected static $defaultName = 'app:import:go-euro';

    /**
     * @var ApiProcessor
     */
    private $apiProcessor;

    /**
     * @var JsonConverter
     */
    private $converter;

    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    public function __construct(ApiProcessor $apiProcessor, JsonConverter $converter, TransactionProcessor $transactionProcessor)
    {
        $this->apiProcessor = $apiProcessor;
        $this->converter = $converter;
        $this->transactionProcessor = $transactionProcessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Imports transactions from GoEuro JSON API.');
        $this->addOption('from', 'f', InputArgument::OPTIONAL, 'Import range (from)');
        $this->addOption('to', 't', InputArgument::OPTIONAL, 'Import range (to)');
        $this->addOption('actual', 'a', InputArgument::OPTIONAL, 'Travel date status');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dateRange = null;
        $importRange = '';

        if (($from = $input->getOption('from')) && ($to = $input->getOption('to'))) {
            $dateRange = [
                'from' => new \DateTime($from),
                'to' => new \DateTime($to)
            ];

            $fromDate = $dateRange['from']->format('Y-m-d');
            $toDate = $dateRange['to']->format('Y-m-d');

            $importRange = " Import range: From $fromDate to $toDate";
        }

        $transactions = $this->converter->convertTransactionsResponse(
            json_encode($this->apiProcessor->getTransactions($dateRange))
        );

        $transactionsImported = $this->transactionProcessor->addToLog(
            $transactions,
            $input->getOption('actual') == 1 ? true : false
        );


        $output->writeln(sprintf('%d transactions imported.%s',
            $transactionsImported,
            $importRange
        ));
    }
}
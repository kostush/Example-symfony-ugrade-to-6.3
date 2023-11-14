<?php

namespace App\Service\Distribusion;

use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Distribusion\Transaction;
use App\Service\EmailNotifier;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class TicketProcessor
{
    /**
     * @var string
     */
    private $qrCodeGeneratorUrl;

    /**
     * @var string
     */
    private $qrCodeApiEndpoint;

    /**
     * @var string
     */
    private $authLogin;

    /**
     * @var string
     */
    private $authPassword;

    /**
     * @var RideCodeGenerator
     */
    private $codeGenerator;

    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    public function __construct(string $qrCodeGeneratorUrl, string $qrCodeApiEndpoint, string $authLogin, string $authPassword, RideCodeGenerator $codeGenerator, EmailNotifier $emailNotifier)
    {
        $this->qrCodeGeneratorUrl = $qrCodeGeneratorUrl;
        $this->qrCodeApiEndpoint = $qrCodeApiEndpoint;
        $this->authLogin = $authLogin;
        $this->authPassword = $authPassword;
        $this->codeGenerator = $codeGenerator;
        $this->emailNotifier = $emailNotifier;
    }

    public function getTicketData(Transaction $transaction)
    {
        $client = new Client();

        try {
            $response = $client->post($this->qrCodeApiEndpoint, [
                'json' => $this->getRequestData($transaction),
                'auth' => [
                    $this->authLogin,
                    $this->authPassword
                ]
            ]);
        } catch (\Exception $ex) {
//            $this->emailNotifier->notifyAboutError(
//                $ex->getMessage(),
//                json_encode($transaction),
//                'Distribusion error notification'
//            );
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        return $this->getResponseData($response);
    }

    private function getRequestData(Transaction $transaction)
    {
        $data = [];
        $data['currency'] = $transaction->getCurrency();
        $data['price'] = $transaction->getPrice();
        $data['adults'] = $transaction->getTotalPassengers();
        $data['children'] = 0;
        $data['infants'] = 0;
        $data['rideDate'] = $transaction->getDepartureDatetime()->format('dmYHi');
        $data['fromId'] = $this->codeGenerator->convertDistribusionCode($transaction, 'departure');
        $data['toId'] = $this->codeGenerator->convertDistribusionCode($transaction, 'arrival');
        $data['transactionId'] = $transaction->getTicketNr();
        $data['fullName'] = $transaction->getCustomerName();
        $name = $this->splitName($transaction->getCustomerName());
        $data['firstName'] = $name['first'];
        $data['lastName'] = $name['last'];
        $data['rideDaily'] = 0;

        return $data;
    }

    private function getResponseData(ResponseInterface $response)
    {
        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'qrCodeData' => $data['qrCode'],
            'qrCodeUrl' => $this->qrCodeGeneratorUrl . urlencode($data['qrCode'])
        ];
    }

    private function splitName($name)
    {
        $result = explode(' ', $name, 2);

        return [
            'first' => $result[0],
            'last' => isset($result[1]) ?
                $result[1] : $result[0]
        ];
    }
}
<?php

namespace App\Service\GoEuro;

use App\Mapping\GoEuro\Transaction;
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

    public function __construct(string $qrCodeGeneratorUrl, string $qrCodeApiEndpoint, string $authLogin, string $authPassword, RideCodeGenerator $codeGenerator)
    {
        $this->qrCodeGeneratorUrl = $qrCodeGeneratorUrl;
        $this->qrCodeApiEndpoint = $qrCodeApiEndpoint;
        $this->authLogin = $authLogin;
        $this->authPassword = $authPassword;
        $this->codeGenerator = $codeGenerator;
    }

    public function getTicketData(Transaction $transaction)
    {
        $client = new Client();

        $response = $client->post($this->qrCodeApiEndpoint, [
            'json' => $this->getRequestData($transaction),
            'auth' => [
                $this->authLogin,
                $this->authPassword
            ]
        ]);

        return $this->getResponseData($response);
    }

    private function getRequestData(Transaction $transaction)
    {
        $this->fixTravelDate($transaction);

        $data = [];
        $data['currency'] = $transaction->getPrice()->getCurrency();
        $data['price'] = $this->getFormattedPrice($transaction);
        $data['adults'] = 1;
        $data['children'] = 0;
        $data['infants'] = 0;
        $data['rideDate'] = $transaction->getTravelDate()->format('dmYHi');
        $data['fromId'] = $this->codeGenerator->convertGoEuroCode($transaction->getOriginCode());
        $data['toId'] = $this->codeGenerator->convertGoEuroCode($transaction->getDestinationCode());
        $data['transactionId'] = $transaction->getTicketCode();
        $data['firstName'] = $transaction->getPassengerName()->getFirstName();
        $data['lastName'] = $transaction->getPassengerName()->getLastName();
        $data['rideDaily'] = 0;

        return $data;
    }

    private function getResponseData(ResponseInterface $response)
    {
        $data = json_decode($response->getBody()->getContents(), true);

        // todo handle incorrect response

        return [
            'qrCodeData' => $data['qrCode'],
            'qrCodeUrl' => $this->qrCodeGeneratorUrl . urlencode($data['qrCode'])
        ];
    }

    private function fixTravelDate(Transaction $transaction)
    {
        $currentMinute = (int) $transaction->getTravelDate()->format('i');

        if ($currentMinute === 59) { // fix midnight ride bug
            $transaction->getTravelDate()->modify('+1 minutes');
        }
    }

    private function getFormattedPrice(Transaction $transaction)
    {
        $formattedPrice = $transaction->getPrice()->getLowestUnitValue() / 100;

        if ($transaction->getType() === 'cancellation' && $formattedPrice > 0) {
            $formattedPrice = -$formattedPrice;
        }

        return $formattedPrice;
    }
}
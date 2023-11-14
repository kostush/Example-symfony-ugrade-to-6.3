<?php

namespace App\Service\Urbi;

use App\Mapping\Urbi\Transaction;
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

    public function __construct(string $qrCodeGeneratorUrl, string $qrCodeApiEndpoint, string $authLogin, string $authPassword)
    {
        $this->qrCodeGeneratorUrl = $qrCodeGeneratorUrl;
        $this->qrCodeApiEndpoint = $qrCodeApiEndpoint;
        $this->authLogin = $authLogin;
        $this->authPassword = $authPassword;
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
        $data['adults'] = $transaction->getPassengerTypes()->getAdults();
        $data['children'] = $transaction->getPassengerTypes()->getChildren();
        $data['infants'] = $transaction->getPassengerTypes()->getInfants();
        $data['rideDate'] = $transaction->getTravelDate()->format('dmYHi');
        $data['fromId'] = $transaction->getOriginCode();
        $data['toId'] = $transaction->getDestinationCode();
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
        $formattedPrice = $transaction->getPrice()->getAmount() / 100;

        if ($transaction->getType() === 'cancellation' && $formattedPrice > 0) {
            $formattedPrice = -$formattedPrice;
        }

        return $formattedPrice;
    }
}
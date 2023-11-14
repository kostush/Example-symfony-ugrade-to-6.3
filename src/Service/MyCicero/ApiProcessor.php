<?php

namespace App\Service\MyCicero;

use GuzzleHttp\Client;

class ApiProcessor
{
    const DATE_FORMAT = 'Y-m-d\TH:i:s.000\Z';

    /**
     * @var string
     */
    private $authUrl;

    /**
     * @var string
     */
    private $importUrl;

    /**
     * @var string
     */
    private $portalLogin;

    /**
     * @var string
     */
    private $portalPassword;

    public function __construct(string $authUrl, string $importUrl, string $portalLogin, string $portalPassword)
    {
        $this->authUrl = $authUrl;
        $this->importUrl = $importUrl;
        $this->portalLogin = $portalLogin;
        $this->portalPassword = $portalPassword;
    }

    public function getTransactions(?array $dateRange)
    {
        $client = new Client();
        $token = $this->getAuthToken();

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ]
            ];

            if ($dateRange) {
                $options += [
                    'query' => [
                        'dateFrom' => $dateRange['from']->format(self::DATE_FORMAT),
                        'dateTo' => $dateRange['to']->format(self::DATE_FORMAT)
                    ]
                ];
            }

            $response = $client->get($this->importUrl, $options);
        } catch (\Exception $ex) {
            // todo: handle
        }

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['data'];
    }

    private function getAuthToken()
    {
        $client = new Client();

        try {
            $response = $client->post($this->authUrl, [
                'json' => [
                    'username' => $this->portalLogin,
                    'password' => $this->portalPassword,
                ]
            ]);
        } catch (\Exception $ex) {
            // todo: handle
        }

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['token'];
    }
}
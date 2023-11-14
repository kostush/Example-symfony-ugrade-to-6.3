<?php

namespace App\Service\Schedule;

use GuzzleHttp\Client;

class RidesProcessor
{
    /**
     * @var string
     */
    private $jwtLoginUrl;

    /**
     * @var string
     */
    private $ridesListUrl;

    /**
     * @var string
     */
    private $ridesDataUrl;

    public function __construct(string $jwtLoginUrl, string $ridesListUrl, string $ridesDataUrl)
    {
        $this->jwtLoginUrl = $jwtLoginUrl;
        $this->ridesListUrl = $ridesListUrl;
        $this->ridesDataUrl = $ridesDataUrl;
    }

    public function getSchedule(int $viaggio, ?string $date)
    {
        $token = $this->getToken();
        $ridesListUrl = str_replace(':VIAGGIO', $viaggio, $this->ridesListUrl);

        $client = new Client();

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ];

            if ($this->validateDate($date)) {
                $options += [
                    'query' => [
                        'date' => $date
                    ]
                ];
            }

            $response = $client->get($ridesListUrl, $options);
        } catch (\Exception $ex) {
            return '[]';
        }

        return $response->getBody()->getContents();
    }

    public function getRidesData(int $viaggio)
    {
        $token = $this->getToken();
        $ridesListUrl = str_replace(':VIAGGIO', $viaggio, $this->ridesDataUrl);

        $client = new Client();

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ];

            $response = $client->get($ridesListUrl, $options);
        } catch (\Exception $ex) {
            return '[]';
        }

        return $response->getBody()->getContents();
    }

    private function getToken()
    {
        $client = new Client();

        $response = $client->post($this->jwtLoginUrl, [
            'form_params' => [
                '_username' => 'testoperator@test.com',
                '_password' => 'testing'
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['token'];
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }
}
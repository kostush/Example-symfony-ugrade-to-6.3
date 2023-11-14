<?php


namespace App\Service\Autostradale;

use App\Exception\ApiException;
use App\Exception\ApiProblem;
use GuzzleHttp\Client;

class AuthProcessor
{
    private $authLogin;

    private $authPassword;

    private $authUrl;

    private $authData;

    public function __construct(string $authLogin, string $authPassword, string $authUrl)
    {
        $this->authLogin = $authLogin;
        $this->authPassword = $authPassword;
        $this->authUrl = $authUrl;
    }

    public function getApiToken(): string
    {
        if (!$this->authData) {
            $this->setAuthData();
        }

        return $this->authData['Token'];
    }

    public function getOperatorId(): int
    {
        if (!$this->authData) {
            $this->setAuthData();
        }

        return $this->authData['Id'];
    }

    public function getLineId(): int
    {
        if (!$this->authData) {
            $this->setAuthData();
        }

        return (int) $this->authData['Sellable'][0]['linee'][0];
    }

    private function setAuthData()
    {
        $client = new Client();

        try {
            $response = $client->get($this->authUrl, [
                'query' => [
                    'username' => $this->authLogin,
                    'password' => $this->authPassword
                ]
            ]);
            $this->authData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $ex) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_USER_CREDENTIALS_INVALID))->set('errorMessage', $ex->getMessage()));
        }
    }
}
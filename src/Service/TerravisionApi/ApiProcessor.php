<?php


namespace App\Service\TerravisionApi;


use App\Model\Credentials;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class ApiProcessor
{
    const USERNAME_KEY_STRING = 'userName';
    const PASSWORD_KEY_STRING = 'password';

    /**
     * @var string
     */
    protected $jwtLoginUrl;

    /**
     * @param string $userName
     * @param string $password
     * @return string
     */
    protected function getJwtToken(string $userName, string $password): string
    {
        $client = new Client();

        $response = $client->post($this->jwtLoginUrl, [
            'form_params' => [
                '_username' => $userName,
                '_password' => $password
            ]
        ]);

        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['token'];
    }

    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    protected function isValidDateFormat(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * @param string|null $userName
     * @param string|null $password
     * @return array
     */
    protected function getRequestOptions(string $userName = null, string $password = null): array
    {

        $jwtUserName = null;
        $jwtPassword = null;

        $credentials = $this->getCredentialsFromRequest();

        if (!empty($userName) && !empty($password)) {
            $credentials = new Credentials($userName, $password);
        }

        if (!$credentials instanceof Credentials) {
            throw new UnauthorizedHttpException('Unauthorized request');
        }

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getJwtToken($credentials->getUsername(), $credentials->getPassword())
            ]
        ];

        return $options;
    }

    /**
     * @return Credentials|null
     */
    protected function getCredentialsFromRequest(): ?Credentials
    {
        $request = Request::createFromGlobals();
        $headers = $request->server->getHeaders();

        if(!empty($headers['AUTHORIZATION']) && (empty($userName) || empty($password))){
            $authArray = explode(" ", $headers['AUTHORIZATION']);

            if($authArray[0] != 'Basic'){
                throw new UnauthorizedHttpException('Unauthorized request');
            }

            $unPw = explode(":", base64_decode($authArray[1]));

            return new Credentials($unPw[0], $unPw[1]);
        }

        return null;
    }
}
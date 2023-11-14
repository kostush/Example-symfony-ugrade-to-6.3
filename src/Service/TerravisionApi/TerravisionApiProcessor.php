<?php


namespace App\Service\TerravisionApi;

use App\Entity\EncryptedToken;
use App\Service\RedisClient;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use RedisException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TerravisionApiProcessor extends ApiProcessor
{
    const ENCRYPTION_KEY = '571eff357c376fe62222f3efc8cd18bc91e7d1800f435585666076b6c366595d';

    const INITIALISATION_VECTOR = 'f3117d2ea0d0750a';

    const ENCRYPT_METHOD = 'AES-256-CBC';

    /**
     * @var string
     */
    private $directionUrl;

    /**
     * @var string
     */
    private $serviceUrl;

    /**
     * @var string
     */
    private $cityUrl;

    /**
     * @var string
     */
    private $citiesUrl;

    /**
     * @var string
     */
    private $ridesListUrl;

    /**
     * @var string
     */
    private $qrCodeGeneratorUrl;

    /**
     * @var string
     */
    private $purchaseTicketUrl;

    /**
     * @var string
     */
    private $downloadTicketUrl;

    /**
     * @var string
     */
    private $validateTicketUrl;

    /**
     * @var string
     */
    private $obliterateTicketUrl;

    /**
     * @var string
     */
    private $getPassengersListUrl;

    /**
     * @var string
     */
    private $eTicketRidesListUrl;

    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $eTicketingTicketDownloadAttempts;

    /**
     * @var RedisClient
     */
    private $redisClient;

    /**
     * TerravisionApiProcessor constructor.
     * @param string $jwtLoginUrl
     * @param string $ridesDataUrl
     * @param string $serviceDataUrl
     * @param string $cityDataUrl
     * @param string $citiesListUrl
     * @param string $ridesListUrl
     * @param string $qrCodeGeneratorUrl
     * @param string $purchaseTicketUrl
     * @param string $downloadTicketUrl
     * @param string $validateTicketUrl
     * @param string $obliterateTicketUrl
     * @param string $getPassengersListUrl
     * @param int $eTicketingTicketDownloadAttempts
     */
    public function __construct(
        string $jwtLoginUrl,
        string $ridesDataUrl,
        string $serviceDataUrl,
        string $cityDataUrl,
        string $citiesListUrl,
        string $ridesListUrl,
        string $qrCodeGeneratorUrl,
        string $purchaseTicketUrl,
        string $downloadTicketUrl,
        string $validateTicketUrl,
        string $obliterateTicketUrl,
        string $getPassengersListUrl,
        string $eTicketRidesListUrl,
        int $eTicketingTicketDownloadAttempts,
        EntityManagerInterface $entityManager,
        RedisClient $redisClient
    )
    {
        $this->jwtLoginUrl = $jwtLoginUrl;
        $this->directionUrl = $ridesDataUrl;
        $this->serviceUrl = $serviceDataUrl;
        $this->cityUrl = $cityDataUrl;
        $this->citiesUrl = $citiesListUrl;
        $this->ridesListUrl = $ridesListUrl;
        $this->qrCodeGeneratorUrl = $qrCodeGeneratorUrl;
        $this->purchaseTicketUrl = $purchaseTicketUrl;
        $this->downloadTicketUrl = $downloadTicketUrl;
        $this->validateTicketUrl = $validateTicketUrl;
        $this->obliterateTicketUrl = $obliterateTicketUrl;
        $this->getPassengersListUrl = $getPassengersListUrl;
        $this->eTicketRidesListUrl = $eTicketRidesListUrl;
        $this->eTicketingTicketDownloadAttempts =$eTicketingTicketDownloadAttempts;
        $this->guzzleClient = new Client();
        $this->entityManager = $entityManager;
        $this->redisClient = $redisClient;
    }

    /**
     * @return string
     */
    public function getCities(): string
    {
        $response = $this->guzzleClient->get($this->citiesUrl, $this->getRequestOptions());

        return $response->getBody()->getContents();
    }

    /**
     * @param int $cityId
     * @return string
     */
    public function getCityData(int $cityId): string
    {
        $cityUrl = str_replace(':CITY', $cityId, $this->cityUrl);

        $response = $this->guzzleClient->get($cityUrl, $this->getRequestOptions());

        return $response->getBody()->getContents();
    }

    /**
     * @param int $directionId
     * @return string
     */
    public function getDirectionData(int $directionId): string
    {
        $directionUrl = str_replace(':VIAGGIO', $directionId, $this->directionUrl);

        $response = $this->guzzleClient->get($directionUrl, $this->getRequestOptions());
        $responseArray = json_decode($response->getBody()->getContents(), true);

        //Removing some data as its not necessary to be exposed
        unset($responseArray['route']);
        unset($responseArray['status']);

        return json_encode($responseArray);
    }

    /**
     * @param int $serviceId
     * @return string
     */
    public function getSeviceData(int $serviceId): string
    {
        $serviceUrl = str_replace(':SERVICE', $serviceId, $this->serviceUrl);
        $response = $this->guzzleClient->get($serviceUrl, $this->getRequestOptions());

        return $response->getBody()->getContents();
    }

    /**
     * @param int $directionId
     * @param string $date
     * @return string
     */
    public function getSchedule(int $directionId, string $date, bool $eTicket = false): string
    {
        if (!$this->isValidDateFormat($date)) {
            throw new BadRequestHttpException('Invalid date format');
        }
        $baseRideListUrl = $eTicket ? $this->eTicketRidesListUrl: $this->ridesListUrl;

        $ridesListUrl = str_replace(':VIAGGIO', $directionId, $baseRideListUrl);

        try{
            $this->redisClient->init();
            $response = $this->redisClient->get($ridesListUrl.$date);
        }catch(RedisException $e){
            // log redis Exception
            $response = false;
        }

        if(!$response){
            $options = $this->getRequestOptions();
            $options += [
                'query' => [
                    'date' => $date
                ]
            ];

            $response = ($this->guzzleClient->get($ridesListUrl, $options))
                ->getBody()
                ->getContents();

            try{
                $this->redisClient->setex($ridesListUrl.$date, $this->redisClient->getRedisExpirationTimeout(),$response);
            }catch(RedisException $e){
                // log redis Exception
            }
        }

        return $response;
    }

    /**
     * @param int $purchaseOrderItemId
     * @return string
     */
    public function downloadTicket(int $purchaseOrderItemId): string
    {
        $serviceUrl = str_replace(':ORDER', $purchaseOrderItemId, $this->downloadTicketUrl);

        $response = $this->guzzleClient->get($serviceUrl, $this->getRequestOptions());

        return $response->getBody()->getContents();
    }

    private function purchaseTicketSendRequest(string $content): Response
    {
        $options = $this->getRequestOptions();

        $contentArray = json_decode($content, true);

        if (!isset($contentArray['rideDateTime']) || !$this->isValidDateFormat($contentArray['rideDateTime'], 'Y-m-d H:i')) {
            throw new BadRequestHttpException('Invalid date format');
        }

        if (isset($contentArray['returnRideDateTime']) && !$this->isValidDateFormat($contentArray['returnRideDateTime'], 'Y-m-d H:i')) {
            throw new BadRequestHttpException('Invalid date format');
        }

        $dateTime = new \DateTime($contentArray['rideDateTime']);
        $now = new \DateTime();

        if ($dateTime <= $now) {
            throw new BadRequestHttpException('Date is in the past');
        }

        //Default value
        $contentArray['rideDaily'] = 0;

        //Mapping of the indexes
        $contentArray['rideDate'] = $contentArray['rideDateTime'];
        $contentArray['fromId'] = $contentArray['fromStopId'];
        $contentArray['toId'] = $contentArray['toStopId'];

        if (isset($contentArray['returnRideDateTime'])) {
            $contentArray['returnRideDate'] = $contentArray['returnRideDateTime'];
        }

        $options += [
            'json' => $contentArray,
        ];

        return $this->guzzleClient->post($this->purchaseTicketUrl, $options);
    }

    /**
     * @param string $content
     * @return string
     * @throws \Exception
     */
    public function purchaseTicket(string $content): string
    {
        try {
            $response = $this->purchaseTicketSendRequest($content);
            $responseArray = json_decode($response->getBody()->getContents(), true);

            $translatedResponseArray = [
                'qrCodeData' => $responseArray['qrCode'],
                'qrCodeUrl' => $this->qrCodeGeneratorUrl . $responseArray['qrCode'],
                'purchaseOrderId' => $responseArray['purchaseOrderId']
            ];

            if (!empty($responseArray['returnQrCode'])) {
                $translatedResponseArray['returnQrCode'] = $responseArray['returnQrCode'];
                $translatedResponseArray['returnQrCodeUrl'] = $this->qrCodeGeneratorUrl . $responseArray['returnQrCode'];
                $translatedResponseArray['returnPurchaseOrderId'] = $responseArray['returnPurchaseOrderId'];
            }

        } catch (ClientException $e) {

            //Parsing the error response from booking app
            $errorMessage = substr($e->getMessage(), strrpos($e->getMessage(), 'response:') + strlen('response:'));

            if ($e->getCode() == HttpResponse::HTTP_BAD_REQUEST && isset(json_decode($errorMessage, true)['ErrorMessage'])) {
                throw new BadRequestHttpException(json_decode($errorMessage, true)['ErrorMessage']);
            }

            throw $e;
        }

        return json_encode($translatedResponseArray);
    }

    /**
     * @param string $content
     * @return string
     */
    public function purchaseTicketByStaff(string $content): string
    {
        try {
            $response = $this->purchaseTicketSendRequest($content);
            $responseArray = json_decode($response->getBody()->getContents(), true);

            $credentials = $this->getCredentialsFromRequest();
            $tokenData = [
                self::USERNAME_KEY_STRING => $credentials->getUsername(),
                self::PASSWORD_KEY_STRING => $credentials->getPassword(),
                'purchaseOrderId' => $responseArray['purchaseOrderId']
            ];

            $encryptedToken = $this->encryptData(json_encode($tokenData));
            $this->saveEncryptedTokenToDb($encryptedToken);

            $translatedResponseArray = [
                'encryptedToken' => $encryptedToken
            ];


        } catch (ClientException $e) {

            //Parsing the error response from booking app
            $errorMessage = substr($e->getMessage(), strrpos($e->getMessage(), 'response:') + strlen('response:'));

            if ($e->getCode() == HttpResponse::HTTP_BAD_REQUEST && isset(json_decode($errorMessage, true)['ErrorMessage'])) {
                throw new BadRequestHttpException(json_decode($errorMessage, true)['ErrorMessage']);
            }

            throw $e;
        }

        return json_encode($translatedResponseArray);
    }

    /**
     * @param string $qrCodeData
     * @param string $rideDateTime
     * @return string
     */
    public function validateTicket(string $qrCodeData, string $rideDateTime): string
    {
        $rideDateFormat = 'Y-m-d H:i';

        if (!$this->isValidDateFormat($rideDateTime, $rideDateFormat)) {
            throw new BadRequestHttpException('Invalid date format');
        }

        $options = $this->getRequestOptions();

        $rideDateTime = $rideDateTime . ':00'; // we adjust datetime string format cos its needed for search in DB on "booking" side
        $validateTicketUrl = str_replace(':QRCODE', $qrCodeData, $this->validateTicketUrl);
        $validateTicketUrl = str_replace(':RIDEDATETIME', $rideDateTime, $validateTicketUrl);

        $response = $this->guzzleClient->get($validateTicketUrl, $options);

        $responseArray = json_decode($response->getBody()->getContents(), true);

        $translatedResponseArray = [
            'ticketOwner' => $responseArray['fullName'],
            'sellingDateTime' => $responseArray['ticket']['selling_date_time'],
            'status' => $responseArray['ticket']['status'],
            'ticketNumber' => $responseArray['ticketNumber'],
            'directionCode' => $responseArray['ticket']['viaggio_code'],
            'adults' => $responseArray['ticket']['adult'],
            'children' => $responseArray['ticket']['child'],
            'departureBusStop' => $responseArray['ticket']['departure_bus_stop'],
            'arrivalBusStop' => $responseArray['ticket']['arrival_bus_stop'],
            'rideDateTime' => \DateTime::createFromFormat(\DateTime::ISO8601, $responseArray['ticket']['ride_date_time'])->format($rideDateFormat),
            'ticketValidity' => $responseArray['validity'],
        ];

        return json_encode($translatedResponseArray);
    }

    /**
     * @param string $qrCodeData
     * @param string $rideDateTime
     * @return string
     */
    public function obliterateTicket(string $qrCodeData, string $rideDateTime): string
    {
        $rideDateFormat = 'Y-m-d H:i';

        if (!$this->isValidDateFormat($rideDateTime, $rideDateFormat)) {
            throw new BadRequestHttpException('Invalid date format');
        }

        $obliterationDateTime = $rideDateTime . ':00'; // we adjust datetime string format cos its needed for search in DB on "booking" side

        $options = $this->getRequestOptions();

        $obliterateTicketUrl = str_replace(':QRCODE', $qrCodeData, $this->obliterateTicketUrl);
        $obliterateTicketUrl = str_replace(':OBLITERATIONDATETIME', $obliterationDateTime, $obliterateTicketUrl);

        try {
            $response = $this->guzzleClient->patch($obliterateTicketUrl, $options);
        } catch (ClientException $e) {

            //Parsing the error response from booking app
            $errorMessage = substr($e->getMessage(), strrpos($e->getMessage(), 'response:') + strlen('response:'));

            if ($e->getCode() == HttpResponse::HTTP_BAD_REQUEST && isset(json_decode($errorMessage, true)['ErrorMessage'])) {
                throw new BadRequestHttpException(json_decode($errorMessage, true)['ErrorMessage']);
            }

            throw $e;
        }

        $responseArray = json_decode($response->getBody()->getContents(), true);

        return json_encode($responseArray);
    }

    /**
     * @param int $directionId
     * @param string $rideDateTime
     * @return string
     */
    public function getPassengersList(int $directionId, string $rideDateTime): string
    {
        $rideDateFormat = 'Y-m-d H:i';

        if (!$this->isValidDateFormat($rideDateTime, $rideDateFormat)) {
            throw new BadRequestHttpException('Invalid date format');
        }

        $rideDateTime = $rideDateTime . ':00'; // we adjust datetime string format cos its needed for search in DB on "booking" side

        $options = $this->getRequestOptions();

        $getPassengersListUrl = str_replace(':VIAGGIO', $directionId, $this->getPassengersListUrl);
        $getPassengersListUrl = str_replace(':RIDEDATETIME', $rideDateTime, $getPassengersListUrl);

        $response = $this->guzzleClient->get($getPassengersListUrl, $options);

        $responseArray = json_decode($response->getBody()->getContents(), true);

        $translatedResponseArray = [];

        if (!empty($responseArray['tickets'])) {
            foreach ($responseArray['tickets'] as $key => $ticket) {
                $translatedResponseArray[$key]['ticketNumber'] = $ticket['ticketNumber'];
                $translatedResponseArray[$key]['fullName'] = $ticket['fullName'];
            }
        }

        return json_encode($translatedResponseArray);
    }

    /**
     * @param string $encryptedToken
     * @return string
     */
    public function downloadTicketByEncryptedToken(string $encryptedToken): string
    {
        $decryptedData = json_decode($this->decryptData($encryptedToken), true);

        if (empty($decryptedData[self::USERNAME_KEY_STRING]) || empty($decryptedData[self::PASSWORD_KEY_STRING]) || empty($decryptedData['purchaseOrderId'])) {
            throw new BadRequestHttpException('Bad Request, no required data');
        }

        $encriptedTokenEntity = $this->getEncryptedTokenFromDb($encryptedToken);

        $purchaseOrderItemId = $decryptedData['purchaseOrderId'];
        $serviceUrl = str_replace(':ORDER', $purchaseOrderItemId, $this->downloadTicketUrl);

        $response = $this->guzzleClient->get($serviceUrl, $this->getRequestOptions($decryptedData[self::USERNAME_KEY_STRING], $decryptedData[self::PASSWORD_KEY_STRING]));
        $this->addDownloadAttemptOrdeleteEncryptedTokenFromDb($encriptedTokenEntity);
        return $response->getBody()->getContents();
    }

    /**
     * Encrypt certain data string with constant encryption key and IV. This
     * method does not provide security but obfuscate data.
     *
     * @param string $data
     * @return string
     */
    protected function encryptData($data)
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException(
                'Encrypt method expect string as argument. ' . gettype($data)
                . ' given.'
            );
        }

        return base64_encode(
            openssl_encrypt(
                $data,
                self::ENCRYPT_METHOD,
                self::ENCRYPTION_KEY,
                0,
                self::INITIALISATION_VECTOR
            )
        );
    }

    /**
     * Decrypt certain data string with constant encryption key and IV.
     *
     * @param string $data
     * @return string
     */
    protected function decryptData($data)
    {
        if (!is_string($data)) {
            throw new \InvalidArgumentException(
                'Encrypt method expect string as argument. ' . gettype($data)
                . ' given.'
            );
        }

        return openssl_decrypt(
            base64_decode($data),
            self::ENCRYPT_METHOD,
            self::ENCRYPTION_KEY,
            0,
            self::INITIALISATION_VECTOR
        );
    }

    /**
     * @param $token
     * @return void
     */
    protected function saveEncryptedTokenToDb($token): void
    {
        $encryptedToken = new EncryptedToken();
        $encryptedToken->setEncryptedToken($token);
        $encryptedToken->setAttempt(0);
        $this->entityManager->persist($encryptedToken);
        $this->entityManager->flush();
    }

    /**
     * @param $encryptedTokenEntity
     * @return void
     */
    protected function addDownloadAttemptOrDeleteEncryptedTokenFromDb($encryptedTokenEntity): void
    {
        $attempt = $encryptedTokenEntity->getAttempt() + 1;
        if ($attempt >= $this->eTicketingTicketDownloadAttempts){
            $this->entityManager->remove($encryptedTokenEntity);
        } else {
            $encryptedTokenEntity->setAttempt($attempt);

            $this->entityManager->persist($encryptedTokenEntity);
        }
        $this->entityManager->flush();
    }

    /**
     * @param $encryptedToken
     * @return object
     */
    protected function getEncryptedTokenFromDb($encryptedToken): object
    {
        $encryptedTokenEntity = (
            $this->entityManager
                ->getRepository(EncryptedToken::class)
                ->findOneBy(['encryptedToken' => $encryptedToken]));
        if (empty ($encryptedTokenEntity)){
            throw new BadRequestHttpException('Ticket has been already downloaded');
        }
        return $encryptedTokenEntity;
    }
}
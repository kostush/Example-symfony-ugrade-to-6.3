<?php

namespace App\Service\Distribusion;


use App\Entity\DistribusionBookingLog;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Distribusion\Booking;
use App\Mapping\Distribusion\DistribusionMapping;
use App\Repository\DistribusionBookingLogRepository;
use App\Service\MicroserviceLogger;
use App\Service\TerravisionApi\ApiProcessor;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class DistribusionCommonProcessor extends ApiProcessor
{
    const WITH_CHILD_POLICY = [
        790, // Palermo Airport ITPMOFBI
        791, // Palermo city center ITPMOCRS
        875, // Vienna City Center Train Station ATVIEWMB
        876, // Vienna Airport Train Station ATVIEWFB
        884, // Munich Central Train Station DEMUCMEH
        885, // Munich Nordfriedhof DEMUCMNO
        886, // Munich Airport Terminal 2 DEMUCMFU
        887, 888, 889, 890, 891, 892, // Oxford services
        926, 915, 925, 924, 923, 922, 921, 920, 913, 914, 912, 911, 910, 909, 916, 908,  // National express
        907, // Trapani Airport ITTPSTBF
        646, // Palermo Viale Lazio 117 ITPMOPVL
        645, // Palermo Via Libertà Don Bosco
        937, // Vienna Airport ATVIEWFU
        936, // Bratislava Mlynske Nivy Bus Station SKBTSZOB
        640, // Palermo V. Libertà Pzza Gentili ITPMOPVE
    ];

    const BRISTOL_STATIONS = [
        "GBBRSAIR", "GBBRSLIB", "GBBRSRPA", "GBKEYCHU", "GBSAOTCO", "GBQQXSPA"
    ];

    const NATIONAL_EXPRESS_STATIONS = [
        "GBLONBGE", "GBLONSAI", "GBLONSHO", "GBLONLSS", "GBLONWHI", "GBLONMEN",
        "GBLONBOW", "GBLONLQE", "GBLONVRA", "GBLONVSA", "GBLONMAF", "GBLONLBS",
        "GBLONSJW", "GBLONLFA", "GBLONGGE", "GBLONGGE", "GBLONGAN"
    ];

    const PARIS_STATIONS = [
        "FRPARPBE", "FRPARABE"
    ];

    const STATIONS_WITH_SPECIFIC_FARE_FOR_ROUND_TRIP = [
        self::PARIS_STATIONS
    ];

    const STATIONS_WITH_ROUND_TRIP_FARE_SUPPORT = [
        self::NATIONAL_EXPRESS_STATIONS,
        self::PARIS_STATIONS
    ];

    const LIMIT_OF_RETRY_ATTEMPTS = 2;

    const RETRY_METHODS = [
        'getVacancyData',
        'getConnectionData'
    ];

    const ALLOWED_CURRENCIES = ['EUR', 'GBP'];

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $statusUrl;

    /**
     * @var RideCodeGenerator
     */
    protected $rideCodeGenerator;

    /**
     * @var DistribusionBookingLogRepository
     */
    protected $repository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var Client
     */
    protected $guzzleClient;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $retailerPartnerNumber;

    /**
     * @var string
     */
    protected $ordersUrl;

    /**
     * @var string
     */
    protected $findUrl;

    /**
     * @var string
     */
    protected $vacancyUrl;

    /**
     * @var string
     */
    protected $projectDir;

    /**
     * @var string
     */
    protected $ticketsUrl;

    /**
     * @var MicroserviceLogger
     */
    protected $logger;


    /**
     * DistribusionCommonProcessor constructor.
     * @param string $apiKey
     * @param string $projectDir
     * @param string $findUrl
     * @param string $vacancyUrl
     * @param string $ordersUrl
     * @param string $statusUrl
     * @param string $ticketsUrl
     * @param string $retailerPartnerNumber
     * @param RideCodeGenerator $rideCodeGenerator
     * @param EntityManagerInterface $entityManager
     * @param RequestStack $requestStack
     * @param MicroserviceLogger $logger
     */
    public function __construct(
        string $apiKey,
        string $projectDir,
        string $findUrl,
        string $vacancyUrl,
        string $ordersUrl,
        string $statusUrl,
        string $ticketsUrl,
        string $retailerPartnerNumber,
        RideCodeGenerator $rideCodeGenerator,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        MicroserviceLogger $logger
    )
    {
        $this->apiKey = $apiKey;
        $this->statusUrl = $statusUrl;
        $this->rideCodeGenerator = $rideCodeGenerator;
        $this->repository = $entityManager->getRepository(DistribusionBookingLog::class);
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->retailerPartnerNumber = $retailerPartnerNumber;
        $this->ordersUrl = $ordersUrl;
        $this->findUrl = $findUrl;
        $this->vacancyUrl = $vacancyUrl;
        $this->ticketsUrl = $ticketsUrl;
        $this->projectDir = $projectDir;
        $this->logger = $logger;

        //        $this->guzzleClient = new Client();

        $this->guzzleClient = new Client([
            'verify' => false
        ]);  // on local dev to prevent verification error we need to disable it
    }

    /**
     * @param $orderItemId
     * @return DistribusionBookingLog|null
     */
    public function getLogByOrderItemId($orderItemId): ?DistribusionBookingLog
    {
        if (!$orderItemId) {
            return null;
        }

        $query = $this->entityManager
            ->getRepository(DistribusionBookingLog::class)
            ->createQueryBuilder('l')
            ->where('l.orderItemId = :orderItemId')
            ->andWhere('l.ticketUrl IS NOT NULL')
            ->orderBy('l.id', 'DESC')
            ->setParameter('orderItemId', $orderItemId)
            ->setMaxResults(1)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    /**
     * @param array $queryParameters
     * @param array $orderData
     * @param Booking $booking
     * @return DistribusionBookingLog
     * @throws \Exception
     */
    protected function addToLog(array $queryParameters,array $orderData, Booking $booking)
    {
        $log = new DistribusionBookingLog();

        $log
            ->setOrderId($orderData['data']['id'])
            ->setRequest($queryParameters)
            ->setLastResponse($orderData)
            ->setCreatedAt(new \DateTime(
                $orderData['data']['attributes']['created_at']
            ))->setOrderItemId($booking->getOrderItemId());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * @param DistribusionMapping $mappingObject
     */
    public function convertInternalCodes(DistribusionMapping $mappingObject)
    {
        if (is_numeric($mappingObject->getDepartureStationId())) {
            $mappingObject->setConvertedDepartureStationId(
                $this->rideCodeGenerator->convertInternalCode(
                    $mappingObject->getDepartureStationId()
                )
            );
        }

        if (is_numeric($mappingObject->getArrivalStationId())) {
            $mappingObject->setConvertedArrivalStationId(
                $this->rideCodeGenerator->convertInternalCode(
                    $mappingObject->getArrivalStationId()
                )
            );
        }
    }

    public function isConnectionExist(?array $connectionData): bool
    {
        return !empty($connectionData['data']);
    }

    /**
     * @param Booking $booking
     * @return array
     */
    protected function convertVacancyPassengerTypes(Booking $booking): array
    {
        $adults = 0;
        $children = 0;
        $passengers = $booking->getPassengers();

        foreach ($passengers as $key => &$passenger) {

            switch ($passenger['type']) {
                case 'Infant':
                    unset($passengers[$key]); // remove infant from passengers
                    break;
                case 'Child':
                    if (in_array($booking->getDepartureStationId(), self::WITH_CHILD_POLICY)) {
                        $children++;
                    } else {
                        $adults++;
                    }
                    break;
                case 'Adult':
                    $adults++;
                    break;
            }
        }

        $convertedPassengersTypes = [];

        if ($adults) {
            $passengerTypes = [
                'type' => 'PNOS',
                'pax' => $adults
            ];

            $convertedPassengersTypes[] = $passengerTypes;
        }

        if ($children) {
            $passengerTypes = [
                'type' => 'PCIL',
                'pax' => $children
            ];

            $convertedPassengersTypes[] = $passengerTypes;
        }

        return $convertedPassengersTypes;
    }

    /**
     * @param Booking $booking
     * @return array
     */
    protected function convertPassengerTypes(Booking $booking): array
    {
        $passengers = $booking->getPassengers();

        foreach ($passengers as $key => &$passenger) {
            $passenger['first_name'] = $booking->getCustomer()->getFirstName();
            $passenger['last_name'] = $booking->getCustomer()->getLastName();

            switch ($passenger['type']) {
                case 'Infant':
                    //                    $passenger['type'] = 'PINT';
                    unset($passengers[$key]); // remove infant from passengers
                    break;
                case 'Child':
                    if (in_array($booking->getDepartureStationId(), self::WITH_CHILD_POLICY)) {
                        $passenger['type'] = 'PCIL'; // convert to Distribusion format
                    } else {
                        $passenger['type'] = 'PNOS'; // treat Children as Adults
                    }
                    break;
                case 'Adult':
                    $passenger['type'] = 'PNOS';
                    break;
            }
        }

        return $passengers;
    }

    /**
     * @param $orderId
     * @param DistribusionBookingLog $log
     * @param null $sessionId
     * @return null
     * @throws \Exception
     */
    protected function getBookingId($orderId, DistribusionBookingLog $log, $sessionUniqueId = null)
    {
        $apiUrl = str_replace(':ORDER_ID', $orderId, $this->statusUrl);

        try {
            $response = $this->guzzleClient->get($apiUrl, [
                'headers' => [
                    'Api-Key' => $this->apiKey
                ],
                'timeout' => 5, // Response timeout
                'connect_timeout' => 5, // Connection timeout
            ]);

            $statusData = json_decode($response->getBody()->getContents(), true);

            $this->logger->write(
                'getBookingId',
                [
                    'response' => $statusData
                ],
                $sessionUniqueId
            );

        } catch (RequestException $ex) {

            if($sessionUniqueId){
                $this->logger->write(
                    'getBookingIdErrorAPI',
                    [
                        'errorMessage' => $ex->getMessage()
                    ],
                    $sessionUniqueId
                );
            }

            return null;
        }

        if (isset($statusData['data']['attributes']['state']) && $statusData['data']['attributes']['state'] === 'executed') {
            $bookingId = $statusData['data']['relationships']['booking']['data']['id'];

            $this->logBookingId($log, $statusData, $bookingId);

            return $bookingId;
        }

        return null;
    }

    /**
     * @param DistribusionBookingLog $log
     * @param $statusData
     * @param $bookingId
     */
    protected function logBookingId(DistribusionBookingLog $log, $statusData, $bookingId)
    {
        $log->setLastResponse($statusData);
        $log->setBookingId($bookingId);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * @param DistribusionBookingLog $log
     * @param $ticketUrl
     */
    protected function logTicketUrl(DistribusionBookingLog $log, $ticketUrl)
    {
        $log->setTicketUrl($ticketUrl);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * @param $bookingId
     * @param null $sessionUniqueId
     * @return string
     * @throws \Exception
     */
    protected function getTicketUrl($bookingId, $sessionUniqueId = null)
    {
        $rootPath = $this->projectDir . '/public';
        $relativePath = "/distribusion/tickets/$bookingId.pdf";
        $filePath = fopen($rootPath . $relativePath,'w');

        $apiUrl = str_replace(':BOOKING_ID', $bookingId, $this->ticketsUrl);

        try {
            $response = $this->guzzleClient->get($apiUrl, [
                'headers' => [
                    'Api-Key' => $this->apiKey
                ],
                'save_to' => $filePath
            ]);

            $this->addLogo($rootPath . $relativePath);
        } catch (\Exception $ex) {

            if($sessionUniqueId){
                $this->logger->write(
                    'getTicketUrlAPI',
                    [
                        'errorMessage' => $ex->getMessage()
                    ],
                    $sessionUniqueId
                );
            }

            // todo custom error
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        return $relativePath; // todo return valid url
    }

    /**
     * @param $filePath
     */
    protected function addLogo($filePath)
    {
        try {
            $pdf = new \FPDI();
            $pageCount = $pdf->setSourceFile($filePath);

            for ($currentPage = 1; $currentPage <= $pageCount; $currentPage++) {
                $pdf->AddPage();
                $pdf->useTemplate(
                    $pdf->importPage($currentPage)
                );

                $pdf->Image($this->projectDir . '/public/terravision.jpg', 79, 1, 50, 10);
            }

            $pdf->Output($filePath, 'F');
        } catch (\Exception $ex) {
            // go further
        }
    }
}
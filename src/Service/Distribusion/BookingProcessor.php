<?php

namespace App\Service\Distribusion;

use App\Entity\DistribusionBookingLog;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Distribusion\Booking;
use App\Mapping\Distribusion\DistribusionMapping;
use App\Mapping\Distribusion\Price;
use App\Mapping\Distribusion\Schedule;
use App\Service\MicroserviceLogger;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BookingProcessor extends DistribusionCommonProcessor
{
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
        parent::__construct(
            $apiKey,
            $projectDir,
            $findUrl,
            $vacancyUrl,
            $ordersUrl,
            $statusUrl,
            $ticketsUrl,
            $retailerPartnerNumber,
            $rideCodeGenerator,
            $entityManager,
            $requestStack,
            $logger
        );
    }

    /**
     * @param Schedule $schedule
     * @return mixed
     */
    public function getScheduleData(Schedule $schedule): array
    {
        if (!$this->isValidDateFormat($schedule->getDate())) {
            throw new BadRequestHttpException('Invalid date format');
        }

        try {
            $response = $this->guzzleClient->get($this->findUrl, [
                'headers' => [
                    'Api-Key' => $this->apiKey
                ],
                'query' => $this->createScheduleQueryParameters($schedule)
            ]);

            $responseArray = json_decode($response->getBody()->getContents(), true);

            $times = [];
            foreach ($responseArray['data'] as $data) {
                $times[] = (new \DateTime($data['attributes']['departure_time']))->format('Y-m-d H:i:s');
            }

            return $times;

        } catch (\Exception $ex) {
            throw new ApiException(
                (new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)
                )->set('errorMessage', $ex->getMessage())
            );
        }
    }

    /**
     * @param Booking $booking
     * @return array
     * @throws \Exception
     */
    public function getConnectionData(DistribusionMapping $mappingObject): array
    {
        $response = $this->guzzleClient->get($this->findUrl, [
            'headers' => [
                'Api-Key' => $this->apiKey
            ],
            'timeout' => 5, // Response timeout
            'connect_timeout' => 5, // Connection timeout
            'query' => $this->createConnectionQueryParameters($mappingObject)
        ]);

        $responseArray = json_decode($response->getBody()->getContents(), true);

        $this->logger->write(
            'getConnectionDataAPI',
            [
                'response' => $responseArray
            ],
            $mappingObject->getSessionUniqueId()
        );

        if($response->getStatusCode() == Response::HTTP_OK &&
            isset($responseArray['included']) && empty($responseArray['included']) &&
            isset($responseArray['data'] ) && empty($responseArray['data'])) {

            throw new NotFoundHttpException('Empty response with no data');
        }

        return $responseArray;
    }

    /**
     * @param string $methodName
     * @param Booking $booking
     * @param int $numberOfAttemptsLeft
     * @return array
     * @throws \Exception
     */
    public function retry(string $methodName, Booking $booking, int $numberOfAttemptsLeft = self::LIMIT_OF_RETRY_ATTEMPTS): array
    {
        if(!in_array($methodName, self::RETRY_METHODS)){
            throw new \Exception('Bad method was provided for retry');
        }

        while ($numberOfAttemptsLeft > 0) {

            $numberOfAttemptsLeft--;

            try{
                return $this->$methodName($booking);
            } catch (TransferException | NotFoundHttpException $ex) {

                $errorMessage = $ex->getMessage().' , number of attempts left '.$numberOfAttemptsLeft;

                if($ex instanceof TransferException){
                    $errorMessage = $ex->getMessage(). ' .Response timeout, number of attempts left '.$numberOfAttemptsLeft;
                }

                $this->logger->write(
                    $methodName.'ErrorAPI',
                    [
                        'errorMessage' => $errorMessage
                    ],
                    $booking->getSessionUniqueId()
                );

            } catch (\Exception $ex) {
                $this->logger->write(
                    $methodName.'ErrorAPI',
                    [
                        'errorMessage' => $ex->getMessage()
                    ],
                    $booking->getSessionUniqueId()
                );
                throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
            }

            sleep(1);
        }

        throw new ApiException((new ApiProblem(ApiProblem::TYPE_AVAILABILITY_ERROR))->set(
            'errorMessage',
            'No response from Distibusion for '.$methodName.' after '.self::LIMIT_OF_RETRY_ATTEMPTS.' attempts'
        )
        );
    }

    /**
     * @param Booking $booking
     * @return array
     * @throws \Exception
     */
    public function getVacancyData(Booking $booking): array
    {
        $query = $this->createVacancyQueryParameters($booking);

        $response = $this->guzzleClient->get($this->vacancyUrl, [
            'headers' => [
                'Api-Key' => $this->apiKey
            ],
            'timeout' => 5, // Response timeout
            'connect_timeout' => 5, // Connection timeout
            'query' => $query
        ]);

        $vacancyData = json_decode($response->getBody()->getContents(), true);

        $this->logger->write(
            'getVacancyDataAPI',
            [
                'request' => $query,
                'response' => $vacancyData
            ],
            $booking->getSessionUniqueId()
        );

        return $vacancyData;
    }

    public function getPriceData(Price $price, string $type)
    {
        if ($type === 'infants') {

            return 0;
        }

        $query = $this->createPriceQueryParameters($price, $type);

        $response = $this->guzzleClient->get($this->vacancyUrl, [
            'headers' => [
                'Api-Key' => $this->apiKey
            ],
            'timeout' => 5, // Response timeout
            'connect_timeout' => 5, // Connection timeout
            'query' => $query
        ]);

        $responseArray = json_decode($response->getBody()->getContents(), true);

        return $responseArray['data']['attributes']['total_price'];

    }

    protected function createPriceQueryParameters(Price $price, $type)
    {
        $params = [
            "marketing_carrier" => $price->getExtraData()['marketing_carrier'],
            "departure_station" => $price->getConvertedDepartureStationId(),
            "arrival_station" => $price->getConvertedArrivalStationId(),
            "departure_time" => $price->getExtraData()['departure_time'],
            "arrival_time" => $price->getExtraData()['arrival_time'],
            "passengers" => $this->convertPricePassengerTypes($price, $type),
            "currency" => $price->getCurrency()
        ];

        $query = urldecode(http_build_query($params, null, '&'));
        $query = preg_replace("/\[0\]|\[1\]|\[2\]/", '[]', $query);

        return $query;
    }

    protected function convertPricePassengerTypes(Price $price, string $type): array
    {
        $passengerTypes = [];

        if ($type === 'adults') {
            $passengerTypes[] = [
                'type' => 'PNOS',
                'pax' => $price->getAdults()
            ];

            return $passengerTypes;
        }

        if ($type === 'children') {
            if (in_array($price->getDepartureStationId(), DistribusionCommonProcessor::WITH_CHILD_POLICY)) {
                $passengerTypes[] = [
                    'type' => 'PCIL',
                    'pax' => $price->getChildren()
                ];

                return $passengerTypes;
            }

            $passengerTypes[] = [
                'type' => 'PNOS',
                'pax' => $price->getChildren()
            ];

            return $passengerTypes;
        }

        if ($type === 'infants') {

            return [];
        }
    }


    /**
     * @param array|null $connectionData
     * @return array
     */
    public function getExtraData(?array $connectionData) : array
    {
        $connection = $connectionData['data'][0];

        return [
            'marketing_carrier' => $connection['relationships']['marketing_carrier']['data']['id'],
            'departure_time' => $connection['attributes']['departure_time'],
            'arrival_time' => $connection['attributes']['arrival_time']
        ];
    }

    /**
     * @param Booking $booking
     * @param bool $isRoundTrip
     * @return array
     * @throws \Exception
     */
    public function createOrder(Booking $booking, $isRoundTrip = false)
    {
        $this->convertPassengerTypes($booking);
        $queryParameters = [];
        $responseData = [
            'orderId' => null,
            'bookingId' => null,
            'ticketUrl' => null
        ];

        // initial request to create new ticket
        try {
            $queryParameters = $this->createQueryParameters($booking, $isRoundTrip);
            $response = $this->guzzleClient->post($this->ordersUrl, [
                'headers' => [
                    'Api-Key' => $this->apiKey
                ],
                'json' => $queryParameters
            ]);

            $responseArray = json_decode($response->getBody()->getContents(), true);

            $this->logger->write(
                'createOrderAPI',
                [
                    'response' => $responseArray
                ],
                $booking->getSessionUniqueId()
            );
        } catch (\Exception $ex) {
            $this->logger->write(
                'createOrderAPI',
                [
                    'errorMessage' => $ex->getMessage()
                ],
                $booking->getSessionUniqueId()
            );
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        $log = $this->addToLog($queryParameters, $responseArray, $booking);

        $responseData['orderId'] = $responseArray['data']['id'];

        // second request, wait until status is `executed` and get booking id
        $bookingId = null;
        for ($i = 0; $i < 13; $i++) {
            sleep(2); // 26 sec total
            if ($bookingId = $this->getBookingId($responseArray['data']['id'], $log, $booking->getSessionUniqueId())) {
                $responseData['bookingId'] = $bookingId;
                break;
            }
            sleep($i + 1); // 91 sec total
        }

        // third request, use booking id to save ticket pdf and get file url
        if ($bookingId) {
            $responseData['ticketUrl'] = $this->requestStack->getCurrentRequest()->getUriForPath($this->getTicketUrl($bookingId, $booking->getSessionUniqueId()));
            $this->logTicketUrl($log, $responseData['ticketUrl']);
        } else {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', 'Ticket file is not created'));
        }

        return $responseData;
    }

    public function findTicketUrl($orderItemId)
    {
        $bookingLog = $this->entityManager
            ->getRepository(DistribusionBookingLog::class)
            ->findOneBy(['orderItemId' => $orderItemId]);
        if ($bookingLog) {
            return $bookingLog->getTicketUrl();
        } else {
            return null;
        }
    }

    private function createQueryParameters(Booking $booking, $isRoundTrip = false): array
    {
        $currency = "EUR";
        $additionalParams = [];
        $totalPrice = $booking->getPrice();
        $totalPassengers = count($this->convertPassengerTypes($booking));

        // @todo get currency from request (temporary solution)
        $isBritishService = substr($booking->getConvertedDepartureStationId(), 0, 2) == "GB";
        $isSwedishService = substr($booking->getConvertedDepartureStationId(), 0, 2) == "SE";

        $marketingCarrier = $booking->getExtraData()['marketing_carrier'];

        if ($isBritishService) {
            $currency = "GBP";

            if ($this->isBristol($booking)) {
                $currency = "EUR"; // Needs to be changed
            }
        }

        if ($isSwedishService) {
            $currency = "SEK";

            if ($isRoundTrip) {
                $roundTripPrice = 21900;
                $totalPrice = $roundTripPrice * $totalPassengers;
                $additionalParams["fare_class"] = "FARE-2";
            } else {
                $oneWayPrice = 13900;
                $totalPrice = $oneWayPrice * $totalPassengers;
            }
        }

        return [
            "marketing_carrier" => $marketingCarrier,
            "departure_station" => $booking->getConvertedDepartureStationId(),
            "arrival_station" => $booking->getConvertedArrivalStationId(),
            "departure_time" => $booking->getExtraData()['departure_time'],
            "arrival_time" => $booking->getExtraData()['arrival_time'],
            "retailer_partner_number" => $this->retailerPartnerNumber,
            "title" => $booking->getCustomer()->getGender() === 'f' ? 'mrs' : 'mr',
            "first_name" => $booking->getCustomer()->getFirstName(),
            "last_name" => $booking->getCustomer()->getLastName(),
            "email" => $booking->getCustomer()->getEmail(),
            "phone" => $booking->getCustomer()->getPhone(),
            "city" => $booking->getCustomer()->getCity(),
            "zip_code" => $booking->getCustomer()->getZipCode(),
            "street_and_number" => $booking->getCustomer()->getStreetAndNumber(),
            "execute_payment" => false,
            "payment_method" => "demand_note", // todo clarify
            "total_price" => $totalPrice,
            "pax" => $totalPassengers,
            "terms_accepted" => true,
            "locale" => "en",
            "currency" => $currency,
            "send_customer_email" => false, // todo activate later
            "passengers" => $this->convertPassengerTypes($booking)
        ] + $additionalParams;
    }

    /**
     * @param Schedule $schedule
     * @return array
     * @throws \Exception
     */
    private function createScheduleQueryParameters(Schedule $schedule)
    {
        $additionalParams = [];

        $datetime = new \DateTime($schedule->getDate());

        $now = new \DateTime('now');

        if(substr($schedule->getConvertedDepartureStationId(), 0, 2) != "GB"){

            // We add 1 hr gmtOffset for non british services, because the date.timezone=Europe/London in php.ini all other
            // European services have +1 hr time zone
            $now->modify('+ 1 hour');
        }

        if($datetime->format('Y-m-d') == $now->format('Y-m-d')){
            // We setup cutoff time in case if its today to avoid failed booking when customers order few minutes before departure
            $additionalParams['departure_start_time'] = $now->modify('+20 minutes')->format('H:i:s');
        }

        return [
            'departure_stations[]' => $schedule->getConvertedDepartureStationId(),
            'arrival_stations[]' => $schedule->getConvertedArrivalStationId(),
            'departure_date' => $datetime->format('Y-m-d'),
            'pax' => '1',
            'locale' => 'en',
            'currency' => 'EUR'
        ] + $additionalParams;
    }

    /**
     * @param Booking $booking
     * @return array
     * @throws \Exception
     */
    private function createConnectionQueryParameters(DistribusionMapping $mappingObject): array
    {
        $datetime = new \DateTime($mappingObject->getDepartureTime());

        [$date, $time] = [
            $datetime->format('Y-m-d'),
            $datetime->format('H:i:s')
        ];

        [$departureStation, $arrivalStation] = [
            $mappingObject->getConvertedDepartureStationId(),
            $mappingObject->getConvertedArrivalStationId()
        ];

        return [
            'departure_stations[]' => $departureStation,
            'arrival_stations[]' => $arrivalStation,
            'departure_date' => $date,
            'pax' => '1',
            'locale' => 'en',
            'currency' => 'EUR', // @todo add from request
            'departure_start_time' => $time,
            'departure_end_time' => $time
        ];
    }

    private function createVacancyQueryParameters(Booking $booking): string
    {
        $params = [
            "marketing_carrier" => $booking->getExtraData()['marketing_carrier'],
            "departure_station" => $booking->getConvertedDepartureStationId(),
            "arrival_station" => $booking->getConvertedArrivalStationId(),
            "departure_time" => $booking->getExtraData()['departure_time'],
            "arrival_time" => $booking->getExtraData()['arrival_time'],
            "passengers" => $this->convertVacancyPassengerTypes($booking),
            "currency" => 'EUR'
        ];

        $query = urldecode(http_build_query($params, null, '&'));
        $query = preg_replace("/\[0\]|\[1\]|\[2\]/", '[]', $query);

        return $query;
    }

    /**
     * @param Booking $booking
     * @return bool
     */
    public function isBristol(Booking $booking): bool
    {
        return in_array($booking->getConvertedDepartureStationId(), self::BRISTOL_STATIONS);
    }
}
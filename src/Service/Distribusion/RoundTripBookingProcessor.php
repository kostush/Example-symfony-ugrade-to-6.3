<?php

namespace App\Service\Distribusion;

use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Distribusion\Booking;
use App\Service\MicroserviceLogger;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoundTripBookingProcessor extends DistribusionCommonProcessor
{
    /**
     * RoundTripBookingProcessor constructor.
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
     * @param array $bookings
     * @return array
     * @throws \Exception
     */
    public function createBookings(array $bookings): array
    {
        $bookingsData = [];
        $isRoundTrip = count($bookings) > 1;

        try {
            if (!empty($bookings) && $this->isMarketingCarrierWithRoundTripFareSupport($bookings[0])) {

                $bookingLog = null;

                foreach ($bookings as $booking) {
                    if ($bookingLog = $this->getLogByOrderItemId($booking->getOrderItemId())) {

                        $ordersData = [
                            'orderId' => $bookingLog->getOrderId(),
                            'bookingId' => $bookingLog->getBookingId(),
                            'ticketUrl' => $bookingLog->getTicketUrl()
                        ];

                        //We duplicate data for 2 incoming bookings in order to give a response for each of them and to be stored
                        // properly in DB on a book.terravision.eu side in 'third_party_information' table
                        $bookingsData = [$ordersData, $ordersData];
                    }
                }

                if (!empty($bookingsData)) {
                    return $bookingsData;
                }

                foreach ($bookings as $booking) {
                    if($booking->getConvertedDepartureStationId() == null){
                        $this->convertInternalCodes($booking);
                    }
                }

                $connectionData = $this->retry('getConnectionData', $bookings, $isRoundTrip);

                if (!$this->isConnectionExist($connectionData)) {
                    throw new ApiException((new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)));
                }

                foreach ($bookings as $booking) {
                    $booking->setExtraData($this->getConnectionExtraData($connectionData, $booking));
                }

                $vacancyData = $this->retry('getVacancyData', $bookings, $isRoundTrip);

                if (!$vacancyData['data']['attributes']['vacant']) {
                    throw new ApiException((new ApiProblem(ApiProblem::TYPE_AVAILABILITY_ERROR)));
                }

                $ordersData = $this->createOrder($bookings, $isRoundTrip);

                if($isRoundTrip) {
                    //We duplicate data for 2 incoming bookings in order to give a response for each of them and to be stored
                    // properly in DB on a book.terravision.eu side in 'third_party_information' table
                    $bookingsData = [$ordersData, $ordersData];
                }else{
                    $bookingsData[] = $ordersData;
                }

            }
        } catch (\Exception $ex) {
            $this->logger->write(
                'createBookingsErrorAPI',
                [
                    'errorMessage' => $ex->getMessage()
                ],
                $bookings[0]->getSessionUniqueId()
            );

            throw new ApiException(
                (new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage())
            );
        }

        return $bookingsData;
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return array
     * @throws \Exception
     */
    private function createOrder(array $bookings, bool $isRoundTrip = false): array
    {
        $responseData = [
            'orderId' => null,
            'bookingId' => null,
            'ticketUrl' => null
        ];

        try{
            $orderParameters = $this->createOrderRequestParameters($bookings, $isRoundTrip);

            $requestOptions = [
                'headers' => [
                    'Api-Key' => $this->apiKey
                ],
                'json' => $orderParameters
            ];

            $response = $this->guzzleClient->post($this->ordersUrl, $requestOptions);

            $responseArray = json_decode($response->getBody()->getContents(), true);

            $this->logger->write(
                'createOrderAPI',
                [
                    'response' => $responseArray
                ],
                $bookings[0]->getSessionUniqueId()
            );

        } catch (\Exception $ex) {
            $this->logger->write(
                'createOrderAPI',
                [
                    'errorMessage' => $ex->getMessage()
                ],
                $bookings[0]->getSessionUniqueId()
            );
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        $log = $this->addToLog($orderParameters, $responseArray, $bookings[0]);

        $responseData['orderId'] = $responseArray['data']['id'];

        // second request, wait until status is `executed` and get booking id
        $bookingId = null;
        for ($i = 0; $i < 13; $i++) {
            sleep(2); // 26 sec total
            if ($bookingId = $this->getBookingId($responseArray['data']['id'], $log, $bookings[0]->getSessionUniqueId())) {
                $responseData['bookingId'] = $bookingId;
                break;
            }
            sleep($i + 1); // 91 sec total
        }

        // third request, use booking id to save ticket pdf and get file url
        if ($bookingId) {
            $responseData['ticketUrl'] = $this->requestStack->getCurrentRequest()->getUriForPath($this->getTicketUrl($bookingId, $bookings[0]->getSessionUniqueId()));
            $this->logTicketUrl($log, $responseData['ticketUrl']);
        } else {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', 'Ticket file is not created'));
        }

        return $responseData;
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return array
     * @throws \Exception
     */
    private function createOrderRequestParameters(array $bookings, bool $isRoundTrip = false): array
    {
        if (empty($bookings)) {
            throw new \Exception('No bookings where provided');
        }

        $additionalParams = [];
        $totalPrice = $this->getTotalPrice($bookings, $isRoundTrip);

        if ($isRoundTrip) {
            $additionalParams['return_departure_time'] = $bookings[1]->getExtraData()['departure_time'];
            $additionalParams['return_arrival_time'] = $bookings[1]->getExtraData()['arrival_time'];
        }

        if ($isRoundTrip && $this->isMarketingCarrierWithSpecificFareForRoundTrip($bookings[0])) {
            $additionalParams['fare_class'] = 'FARE-2';
        }

        $totalPassengers = count($this->convertPassengerTypes($bookings[0]));
        $marketingCarrier = $bookings[0]->getExtraData()['marketing_carrier'];

        $params = [
            'marketing_carrier' => $marketingCarrier,
            'departure_station' => $bookings[0]->getConvertedDepartureStationId(),
            'arrival_station' => $bookings[0]->getConvertedArrivalStationId(),
            'departure_time' => $bookings[0]->getExtraData()['departure_time'],
            'arrival_time' => $bookings[0]->getExtraData()['arrival_time'],
            'retailer_partner_number' => $this->retailerPartnerNumber,
            'title' => $bookings[0]->getCustomer()->getGender() === 'f' ? 'mrs' : 'mr',
            'first_name' => $bookings[0]->getCustomer()->getFirstName(),
            'last_name' => $bookings[0]->getCustomer()->getLastName(),
            'email' => $bookings[0]->getCustomer()->getEmail(),
            'phone' => $bookings[0]->getCustomer()->getPhone(),
            'city' => $bookings[0]->getCustomer()->getCity(),
            'zip_code' => $bookings[0]->getCustomer()->getZipCode(),
            'street_and_number' => $bookings[0]->getCustomer()->getStreetAndNumber(),
            'execute_payment' => false,
            'payment_method' => 'demand_note', // todo clarify
            'total_price' => $totalPrice,
            'pax' => $totalPassengers,
            'terms_accepted' => true,
            'locale' => 'en',
            'currency' => $bookings[0]->getCurrency(),
            'send_customer_email' => false, // todo activate later
            'passengers' => $this->convertPassengerTypes($bookings[0])
        ] + $additionalParams;

        return $params;
    }

    /**
     * @param Booking $booking
     * @return bool
     */
    public function isNationalExpress(Booking $booking): bool
    {
        if($booking->getConvertedDepartureStationId() == null){
            $this->convertInternalCodes($booking);
        }

        return in_array($booking->getConvertedDepartureStationId(), self::NATIONAL_EXPRESS_STATIONS);
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return array
     * @throws \Exception
     */
    private function createConnectionQueryParameters(array $bookings, bool $isRoundTrip = false): array
    {
        if (empty($bookings)) {
            throw new \Exception('No bookings where provided');
        }

        foreach ($bookings as $booking) {
            if($booking->getConvertedDepartureStationId() == null){
                $this->convertInternalCodes($booking);
            }
        }

        $departureDateTime = new \DateTime($bookings[0]->getDepartureTime());

        [$departureDate, $departureTime] = [
            $departureDateTime->format('Y-m-d'),
            $departureDateTime->format('H:i:s')
        ];

        [$departureStation, $arrivalStation] = [
            $bookings[0]->getConvertedDepartureStationId(),
            $bookings[0]->getConvertedArrivalStationId()
        ];

        $connectionQueryParameters = [
            'departure_stations[]' => $departureStation,
            'arrival_stations[]' => $arrivalStation,
            'departure_date' => $departureDate,
            'pax' => count($bookings[0]->getPassengers()),
            'locale' => 'en',
            'currency' => $bookings[0]->getCurrency(), // @todo add from request
//            'departure_start_time' => $departureTime,
//            'departure_end_time' => $departureTime
        ];

        if ($isRoundTrip) {

            $returnDateTime = new \DateTime($bookings[1]->getDepartureTime());
            $connectionQueryParameters['return_date'] = $returnDateTime->format('Y-m-d');
        }

        return $connectionQueryParameters;
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return array
     * @throws \Exception
     */
    private function createVacancyQueryParameters(array $bookings, bool $isRoundTrip = false): string
    {
        if (empty($bookings)) {
            throw new \Exception('No bookings where provided');
        }

        $params = [
            'marketing_carrier' => $bookings[0]->getExtraData()['marketing_carrier'],
            'departure_station' => $bookings[0]->getConvertedDepartureStationId(),
            'arrival_station' => $bookings[0]->getConvertedArrivalStationId(),
            'departure_time' => $bookings[0]->getExtraData()['departure_time'],
            'arrival_time' => $bookings[0]->getExtraData()['arrival_time'],
            'passengers' => $this->convertVacancyPassengerTypes($bookings[0]),
            'currency' => $bookings[0]->getCurrency()
        ];

        if ($isRoundTrip) {
            $params['return_departure_time'] = $bookings[1]->getExtraData()['departure_time'];
            $params['return_arrival_time'] = $bookings[1]->getExtraData()['arrival_time'];
        }

        if ($isRoundTrip && $this->isMarketingCarrierWithSpecificFareForRoundTrip($bookings[0])) {
            $additionalParams['fare_class'] = 'FARE-2';
        }

        $query = urldecode(http_build_query($params, null, '&'));
        $query = preg_replace('/\[0\]|\[1\]|\[2\]/', '[]', $query);

        return $query;
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return mixed
     * @throws \Exception
     */
    private function getConnectionData(array $bookings, bool $isRoundTrip = false): array
    {
        $query = $this->createConnectionQueryParameters($bookings, $isRoundTrip);

        $requestOptions = [
            'headers' => [
                'Api-Key' => $this->apiKey
            ],
            'timeout' => 5, // Response timeout
            'connect_timeout' => 5, // Connection timeout
            'query' => $query
        ];

        $response = $this->guzzleClient->get($this->findUrl, $requestOptions);

        $responseArray = json_decode($response->getBody()->getContents(), true);

        $this->logger->write(
            'getConnectionDataAPI',
            [
                'response' => $responseArray
            ],
            $bookings[0]->getSessionUniqueId()
        );

        if ($response->getStatusCode() == Response::HTTP_OK &&
            empty($responseArray['included']) && empty($responseArray['data'])) {

            throw new NotFoundHttpException('Empty response with no data');
        }

        return $responseArray;
    }

    /**
     * @param string $methodName
     * @param array $bookings
     * @param bool $isRoundTrip
     * @param int $numberOfAttemptsLeft
     * @return array
     * @throws \Exception
     */
    public function retry(
        string $methodName,
        array $bookings,
        bool $isRoundTrip = false,
        int $numberOfAttemptsLeft = self::LIMIT_OF_RETRY_ATTEMPTS
    ): array
    {
        if (!in_array($methodName, self::RETRY_METHODS)) {
            throw new \Exception('Bad method was provided for retry');
        }


        while ($numberOfAttemptsLeft > 0) {

            $numberOfAttemptsLeft--;

            try {
                return $this->$methodName($bookings, $isRoundTrip);
            } catch (TransferException | NotFoundHttpException $ex) {

                $errorMessage = 'Connections not found, number of attempts left ' . $numberOfAttemptsLeft;

                if ($ex instanceof TransferException) {
                    $errorMessage = $ex->getMessage() . ' .Response timeout, number of attempts left ' . $numberOfAttemptsLeft;
                }

                $this->logger->write(
                    $methodName . 'ErrorAPI',
                    [
                        'errorMessage' => $errorMessage
                    ],
                    $bookings[0]->getSessionUniqueId()
                );

            } catch (\Exception $ex) {
                $this->logger->write(
                    $methodName . 'ErrorAPI',
                    [
                        'errorMessage' => $ex->getMessage()
                    ],
                    $bookings[0]->getSessionUniqueId()
                );
                throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
            }

            sleep(1);
        }

        throw new ApiException((new ApiProblem(ApiProblem::TYPE_AVAILABILITY_ERROR))->set(
            'errorMessage',
            'No response from Distibusion for ' . $methodName . ' after ' . self::LIMIT_OF_RETRY_ATTEMPTS . ' attempts'
        )
        );
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return array
     * @throws \Exception
     */
    private function getVacancyData(array $bookings, bool $isRoundTrip = false): array
    {
        $query = $this->createVacancyQueryParameters($bookings, $isRoundTrip);

        $requestOptions = [
            'headers' => [
                'Api-Key' => $this->apiKey
            ],
            'timeout' => 5, // Response timeout
            'connect_timeout' => 5, // Connection timeout
            'query' => $query
        ];

        $response = $this->guzzleClient->get($this->vacancyUrl, $requestOptions);

        $vacancyData = json_decode($response->getBody()->getContents(), true);

        $this->logger->write(
            'getVacancyDataAPI',
            [
                'request' => $query,
                'response' => $vacancyData
            ],
            $bookings[0]->getSessionUniqueId()
        );

        return  $vacancyData;
    }

    /**
     * @param array|null $connectionData
     * @param Booking $booking
     * @return array
     * @throws \Exception
     */
    public function getConnectionExtraData(array $connectionData, Booking $booking): array
    {
        if(empty($connectionData['data'])) {
            throw new \Exception('No data index in connection data was found');
        }

        $connectionExtraData = [];
        $connectionData = $connectionData['data'];

        $marketingCarrier = $connectionData[0]['relationships']['marketing_carrier']['data']['id'];
        $departureDateTime = new \DateTime($booking->getDepartureTime());

        [$departureDate, $departureTime] = [
            $departureDateTime->format('Y-m-d'),
            $departureDateTime->format('H:i')
        ];

        // $idString format example 'NBEA-FRPARPBE-FRPARABE-2022-05-26T08:35'
        $idString = $marketingCarrier . '-' .
            $booking->getConvertedDepartureStationId() . '-' .
            $booking->getConvertedArrivalStationId() . '-' .
            $departureDate . 'T' .
            $departureTime;

        foreach ($connectionData as $data) {

            if (strpos($data['id'], $idString) !== false) {

                $connectionExtraData = [
                    'marketing_carrier' => $marketingCarrier,
                    'departure_time' => $data['attributes']['departure_time'],
                    'arrival_time' => $data['attributes']['arrival_time']
                ];
            }
        }

        if (empty($connectionExtraData)) {

            $this->logger->write(
                'getConnectionExtraDataErrorAPI',
                [
                    'errorMessage' => $idString . ' was not found in connectionData'
                ],
                $booking->getSessionUniqueId()
            );

            throw new ApiException((new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)));
        }

        return $connectionExtraData;
    }

    /**
     * @param array $bookings
     * @param bool $isRoundTrip
     * @return int
     */
    private function getTotalPrice(array $bookings, bool $isRoundTrip = false): int
    {
        $totalPrice = (int) $bookings[0]->getPrice();

        if ($isRoundTrip) {
            $totalPrice = (int)$bookings[0]->getPrice() + (int)$bookings[1]->getPrice();
        }

        return $totalPrice;
    }


    /**
     * @param Booking $booking
     * @return bool
     */
    public function isMarketingCarrierWithRoundTripFareSupport(Booking $booking): bool
    {
        return $this->isValidBookingForBusStations($booking, self::STATIONS_WITH_ROUND_TRIP_FARE_SUPPORT);
    }

    /**
     * @param Booking $booking
     * @return bool
     */
    public function isMarketingCarrierWithSpecificFareForRoundTrip(Booking $booking): bool
    {
        return $this->isValidBookingForBusStations($booking, self::STATIONS_WITH_SPECIFIC_FARE_FOR_ROUND_TRIP);
    }

    /**
     * @param Booking $booking
     * @param array $busStationsList
     * @return bool
     */
    private function isValidBookingForBusStations(Booking $booking, array $busStationsList): bool
    {
        if($booking->getConvertedDepartureStationId() == null){
            $this->convertInternalCodes($booking);
        }

        $response = false;

        foreach ($busStationsList as $busStations) {

            if(in_array($booking->getConvertedDepartureStationId(), $busStations)) {
                $response = true;
            }
        }

        return $response;
    }
}
<?php

namespace App\Service\Autostradale;

use App\Entity\AutostradaleBookingLog;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Autostradale\Booking;
use App\Mapping\Autostradale\BookingUpdate;
use GuzzleHttp\Client;

class BookingProcessor
{
    /**
     * @var AuthProcessor
     */
    private $authProcessor;

    /**
     * @var LoggerProcessor
     */
    private $loggerProcessor;

    /**
     * @var TicketProcessor
     */
    private $ticketProcessor;

    /**
     * @var string
     */
    private $getBookingRideUrl;

    /**
     * @var string
     */
    private $putBookingCartUrl;

    /**
     * @var string
     */
    private $delBookingCartUrl;

    /**
     * @var string
     */
    private $confirmBookingCartUrl;

    /**
     * @var string
     */
    private $updateBookingUrl;

    public function __construct(AuthProcessor $authProcessor, LoggerProcessor $loggerProcessor, TicketProcessor $ticketProcessor, string $getBookingRideUrl, string $putBookingCartUrl, string $delBookingCartUrl, string $confirmBookingCartUrl, string $updateBookingUrl)
    {
        $this->authProcessor = $authProcessor;
        $this->loggerProcessor = $loggerProcessor;
        $this->ticketProcessor = $ticketProcessor;
        $this->getBookingRideUrl = $getBookingRideUrl;
        $this->putBookingCartUrl = $putBookingCartUrl;
        $this->delBookingCartUrl = $delBookingCartUrl;
        $this->confirmBookingCartUrl = $confirmBookingCartUrl;
        $this->updateBookingUrl = $updateBookingUrl;
    }

    public function checkAvailability(Booking $booking, ?Booking $returnBooking = null)
    {
        $responseData = $this->addToCart($booking, $returnBooking);

        if ($responseData['Result'] === true) {
            $this->deleteCart($responseData['IdCart']);
        } else {
            throw new \Exception('There were some errors while trying adding to cart: ' . implode(', ', $responseData['Errors']));
        }
    }

    public function createBookings(Booking $booking, ?Booking $returnBooking = null)
    {
        /** @var AutostradaleBookingLog $bookingLog */
        if ($bookingLog = $this->loggerProcessor->getLogByOrderItemId($booking->getOrderItemId())) {
            $bookingId = $bookingLog->getBookingId();
            $ticketLink = $bookingLog->getModifiedTicketUrl() ?? $bookingLog->getOriginalTicketUrl();
        } else {
            $responseData = $this->addToCart($booking, $returnBooking, true);

            if ($responseData['Result'] === true) {
                $responseData = $this->confirmCart($responseData['IdCart']);
            } else {
                throw new \Exception('There were some errors while trying adding to cart: ' . implode(', ', $responseData['Errors']));
            }

            $this->loggerProcessor->addResponseData($responseData);

            $bookingId = $responseData['IdBooking'] ?? null;
            $ticketLink = $responseData['LinkTicketPdf'];

            $ticketLink = $this->ticketProcessor->addLogo($bookingId, $ticketLink);

            $this->loggerProcessor->addModifiedTicketLink($ticketLink);
        }

        return [
            'orderId' => $bookingId,
            'bookingId' => $bookingId,
            'ticketUrl' => $ticketLink,
        ];
    }

    public function updateBooking(BookingUpdate $bookingUpdate)
    {
        try {
            $originalBookingId = $bookingUpdate->getBookingId();
            list($orderItemId, $latestBookingId, $latestBookingTickets) =
                $this->loggerProcessor->getOrderItemLatestBookingIdTickets($originalBookingId);

            $client = new Client();
            $requestData = [
                "Culture" => "en-GB",
                "Currency" => "GBP",
                "IdBooking" => $latestBookingId,
                "UserId" => $this->authProcessor->getOperatorId(),
                "Tickets" => $this->getTicketsNewData($bookingUpdate, $latestBookingTickets)
            ];

            $this->loggerProcessor->addRequestData($requestData, $orderItemId);

            $response = $client->post($this->updateBookingUrl, [
                'query' => [
                    'token' => $this->authProcessor->getApiToken()
                ],
                'json' => $requestData
            ]);
            $responseData = json_decode($response->getBody()->getContents(), true);
            $this->loggerProcessor->addResponseData($responseData);

            if ($responseData['Result'] && $responseData['IdCart']) {
                $responseData = $this->confirmCart($responseData['IdCart']);
                $this->loggerProcessor->addResponseData($responseData);
            }

            if ($responseData['Result']) {
                $ticketLink = $responseData['LinkTicketPdf'];

                $ticketLink = $this->ticketProcessor->addLogo($originalBookingId, $ticketLink);
                $this->loggerProcessor->addModifiedTicketLink($ticketLink);
            } else {
                $responseJson = json_encode($responseData);
                if (mb_strpos($responseJson, "UB016") !== false ||
                    mb_strpos($responseJson, "UB099") !== false) {
                    throw new \Exception('There are not enough sits available on this ride');
                } else {
                    throw new \Exception('Update request was unsuccessful');
                }
            }
        } catch (\Exception $ex) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        return [
            'orderId' => $responseData['IdBooking'],
            'bookingId' => $responseData['IdBooking'],
            'ticketUrl' => $ticketLink
        ];
    }

    private function getTicketsNewData(BookingUpdate $bookingUpdate, $latestBookingTickets)
    {
        $oldDate = (int)(new \DateTime($bookingUpdate->getDepartureTime()))->format('Ymd');
        $oldHour = (int)(new \DateTime($bookingUpdate->getDepartureTime()))->format('Hi');

        $departureTickets = [];
        $returnTickets = [];

        foreach ($latestBookingTickets as $ticket) {
            if ((int)$ticket['DepartureTime'] === $oldHour &&
                (int)$ticket['TravelDate'] === $oldDate) {
                $departureTickets[] = $ticket;
            } else {
                $returnTickets[] = $ticket;
            }
        }

        if (!$departureTickets) {
            throw new \Exception('No tickets with this departure time');
        }

        $booking = new Booking();
        $booking->setDepartureStationId($bookingUpdate->getDepartureStationId());
        $booking->setArrivalStationId($bookingUpdate->getArrivalStationId());
        $booking->setDepartureCityId($bookingUpdate->getDepartureCityId());
        $booking->setArrivalCityId($bookingUpdate->getArrivalCityId());
        $booking->setDepartureTime($bookingUpdate->getDepartureTimeNew());
        $booking->setExtraData($bookingUpdate->getExtraData());

        $newTickets = [];

        if (!$returnTickets || true) { // send only departure tickets | Autostradale has updated their API
            $ridesData = $this->getRidesData($booking);

            foreach ($departureTickets as $ticket) {
                $newTickets[] = [
                    "IdNewRide" => $ridesData['departure']['IdRide'],
                    "ticketNumber" => $ticket['IdTicket'],
                    "NewDate" => (int)(new \DateTime($bookingUpdate->getDepartureTimeNew()))->format('Ymd')
                ];
            }
        } else {
            $returnDateString = $returnTickets[0]['TravelDate'] . ' ' .
                sprintf("%04d", (int)$returnTickets[0]['DepartureTime']);
            $returnDate = \DateTime::createFromFormat('Ymd Hi', $returnDateString);

            $returnBooking = new Booking();
            $returnBooking->setDepartureStationId($bookingUpdate->getArrivalStationId());
            $returnBooking->setArrivalStationId($bookingUpdate->getDepartureStationId());
            $returnBooking->setDepartureCityId($bookingUpdate->getArrivalCityId());
            $returnBooking->setArrivalCityId($bookingUpdate->getDepartureCityId());
            $returnBooking->setDepartureTime($returnDate->format('Y-m-d H:i:s'));
            $returnBooking->setExtraData($bookingUpdate->getExtraData());


            // reverse logic if return ride is sent for amendment
            if ($returnBooking->getDepartureTime() > $booking->getDepartureTime()) {
                $ridesData = $this->getRidesData($booking, $returnBooking);

                $departureIdRide = $ridesData['departure']['IdRide'];
                $returnIdRide = $ridesData['return']['IdRide'];
            } else {
                $ridesData = $this->getRidesData($returnBooking, $booking);

                $departureIdRide = $ridesData['return']['IdRide'];
                $returnIdRide = $ridesData['departure']['IdRide'];
            }

            foreach ($departureTickets as $ticket) {
                $newTickets[] = [
                    "IdNewRide" => $departureIdRide,
                    "ticketNumber" => $ticket['IdTicket'],
                    "NewDate" => (int)(new \DateTime($bookingUpdate->getDepartureTimeNew()))->format('Ymd')
                ];
            }

            foreach ($returnTickets as $ticket) {
                $newTickets[] = [
                    "IdNewRide" => $returnIdRide,
                    "ticketNumber" => $ticket['IdTicket'],
                    "NewDate" => (int)(new \DateTime($returnBooking->getDepartureTime()))->format('Ymd')
                ];
            }
        }

        return $newTickets;
    }

    private function addToCart(Booking $booking, ?Booking $returnBooking = null, bool $enableLogging = false): array
    {
        try {
            $client = new Client();
            $requestData = $this->getBookingCartRequestData($booking, $returnBooking);

            if ($enableLogging) {
                $this->loggerProcessor->addRequestData($requestData, $booking->getOrderItemId());
            }

            $response = $client->post($this->putBookingCartUrl, [
                'query' => [
                    'token' => $this->authProcessor->getApiToken()
                ],
                'json' => $requestData
            ]);
            $responseData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $ex) {
            if ($enableLogging) {
                $this->loggerProcessor->addResponseData(['ErrorMessage' => $ex->getMessage()]);
            }
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_VALIDATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        return $responseData;
    }

    private function deleteCart(string $cartId)
    {
        try {
            $client = new Client();
            $response = $client->post($this->delBookingCartUrl, [
                'query' => [
                    'token' => $this->authProcessor->getApiToken(),
                    'IdCart' => $cartId
                ]
            ]);
            $responseData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $ex) {
            // carry on anyway
        }
    }

    private function confirmCart(string $cartId): array
    {
        $client = new Client();
        $response = $client->post($this->confirmBookingCartUrl, [
            'query' => [
                'token' => $this->authProcessor->getApiToken(),
                'IdCart' => $cartId,
                'IdOperatore' => $this->authProcessor->getOperatorId(),
                'Culture' => 'en-GB',
                'AuthId' => '12345',
                'PaymentId' => '12345',
                'TransId' => '12345',
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getBookingCartRequestData(Booking $booking, ?Booking $returnBooking = null): array
    {
        $ridesData = $this->getRidesData($booking, $returnBooking);

        $cartBooking = [
            "DepartureRideSense" => "A",
            "BookingType" => $returnBooking ? 'AR' : 'A',
            "DepartureArrivalTime" => $ridesData['departure']['ArrivalTime'],
            "DepartureDepartureTime" => $ridesData['departure']['DepartureTime'],
            "DepartureDate" => (new \DateTime($booking->getDepartureTime()))->format('Ymd'),
            "FecodiArrival" => $booking->getArrivalStationId(),
            "FecodiDeparture" => $booking->getDepartureStationId(),
            "IdDepartureRide" => $ridesData['departure']['IdRide'],
            "IdLine" => $this->authProcessor->getLineId(),
            "PassengerEmail" => $booking->getCustomer()->getEmail(),
            "PassengerName" => $booking->getCustomer()->getFullName(),
            "Passengers" => $booking->getPassengers()
        ];

        if ($returnBooking) {
            $cartBooking += [
                "ReturnSense" => null,
                "IdRideReturn" => $ridesData['return']['IdRide'],
                "DateReturn" => (new \DateTime($returnBooking->getDepartureTime()))->format('Ymd'),
                "ReturnArrivalTime" => $ridesData['return']['ArrivalTime'],
                "ReturnDepartureTime" => $ridesData['return']['DepartureTime']
            ];
        }

        $requestData = [
            'BookingEmail' => $booking->getCustomer()->getEmail(),
            'ConsensoAttivaNewsLetter' => false,
            'ConsensoPrivacy' => true,
            'ConsensoTrattMarketing' => false,
            'ConsensoTrattSondaggi' => false,
            'Culture' => 'en-GB',
            'Currency' => 'GBP',
            'Environment' => null,
            'IdCart' => '0',
            'IdOperatore' => $this->authProcessor->getOperatorId(),
            'NominativeBooking' => 'uno',
            'PhoneNumberBooking' => $booking->getCustomer()->getPhone(),
            'Bookings' => [
                $cartBooking
            ]
        ];

        return $requestData;
    }

    private function getRidesData(Booking $booking, ?Booking $returnBooking = null)
    {
        $ridesData = [
            'departure' => null,
            'return' => null
        ];

        try {
            $queryParameters = [
                'token' => $this->authProcessor->getApiToken(),
                'CurrencyCode' => 'GBP',
                'Qty' => $booking->getExtraData()['Qty'],
                "BookingType" => $returnBooking ? 'AR' : 'A',
                'IdOperatore' => $this->authProcessor->getOperatorId(),
                'DepartureCtid' => $booking->getDepartureCityId(),
                'DepartureFecodi' => $booking->getDepartureStationId(),
                'ArrivalCtid' => $booking->getArrivalCityId(),
                'ArrivalFecodi' => $booking->getArrivalStationId(),
                'TravelDate' => (new \DateTime($booking->getDepartureTime()))->format('Ymd')
            ];

            if ($returnBooking) {
                $queryParameters += [
                    'ReturnDate' => (new \DateTime($returnBooking->getDepartureTime()))->format('Ymd')
                ];
            }

            $client = new Client();
            $response = $client->get($this->getBookingRideUrl, [
                'query' => $queryParameters
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $ex) {
            throw new \Exception($ex->getMessage()); // event propagation
        }

        if ($responseData['Result'] === true) {
            $departureTime = (new \DateTime($booking->getDepartureTime()))->format('Hi'); // remove all leading zeros
            $returnDepartureTime = $returnBooking ? (new \DateTime($returnBooking->getDepartureTime()))->format('Hi') : '0000';

            foreach ($responseData['Ride'] as $ride) {
                if (!$ridesData['departure'] && $ride['DepartureTime'] === $departureTime &&
                    $ride['DepartureFecodi'] == $booking->getDepartureStationId() &&
                    $ride['ArrivalFecodi'] == $booking->getArrivalStationId()
                ) {
                    $ridesData['departure'] = $ride;
                } elseif ($returnBooking && !$ridesData['return'] &&
                    $ride['DepartureTime'] === $returnDepartureTime &&
                    $ride['DepartureFecodi'] == $returnBooking->getDepartureStationId() &&
                    $ride['ArrivalFecodi'] == $returnBooking->getArrivalStationId()
                ) {
                    $ridesData['return'] = $ride;
                }
            }
        }

        if (empty($ridesData['departure']) || ($returnBooking && empty($ridesData['return']))) {
            if (!empty($queryParameters)) {
                $this->loggerProcessor->addRequestData([
                    $queryParameters,
                    $this->loggerProcessor->objectToArray($booking),
                    $this->loggerProcessor->objectToArray($returnBooking)
                ], $booking->getOrderItemId());
            }
            if (!empty($responseData)) {
                $this->loggerProcessor->addResponseData($responseData);
            }
            throw new \Exception('Ride was not found in ' . json_encode($responseData));
        }

        return $ridesData;
    }
}
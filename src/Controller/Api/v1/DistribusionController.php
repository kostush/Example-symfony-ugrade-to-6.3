<?php

namespace App\Controller\Api\v1;

use App\Controller\Api\BaseApiController;
use App\Entity\DistribusionTransactionLog;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Distribusion\Booking;
use App\Mapping\Distribusion\Schedule;
use App\Service\Distribusion\BookingProcessor;
use App\Service\Distribusion\RoundTripBookingProcessor;
use App\Service\Distribusion\JsonConverter;
use App\Service\Distribusion\RideCodeGenerator;
use App\Service\Distribusion\TicketProcessor;
use App\Service\Distribusion\TransactionProcessor;
use App\Service\MicroserviceLogger;
use App\Service\Schedule\RidesProcessor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @todo add basic auth
 * @Route("/api/v1")
 */
class DistribusionController extends BaseApiController
{
    /**
     * @IsGranted("ROLE_DISTRIBUSION")
     * @Route("/distribusion/import", methods={"POST"}, name="disribusion_import")
     * @OA\RequestBody (
     *     request="order",
     *     description="Send a request with JSON data, that includes all the neccesary information about the ride.",
     *     @OA\JsonContent(example="{
    ""price"": 15,
    ""agency"": ""distribusion"",
    ""currency"": ""EUR"",
    ""commission"": 0,
    ""arrivalStop"": ""13821"",
    ""arrivalStopName"": ""Milan Malpensa Airport Terminal 2"",
    ""ticketNr"": ""ABC123"",
    ""bookingTime"": ""2020/08/10 01:53:07"",
    ""customerMail"": ""test@test.com"",
    ""customerName"": ""Testio Testinno"",
    ""departureStop"": ""14123"",
    ""departureStopName"": ""Milan Centrale Via Giovanni Battista Sammartini"",
    ""arrivalDatetime"": ""2020/08/16 19:40:00"",
    ""customerCountry"": ""DE"",
    ""totalPassengers"": 3,
    ""customerLanguage"": ""en"",
    ""customerTelephone"": ""+07971110220"",
    ""departureDatetime"": ""2020/08/16 20:30:00"",
    ""passengerTypes"": {
        ""adults"": 1,
        ""children"": 1,
        ""infants"": 1
    }
}")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns the QR CODE, if the ticket was successfuly created",
     *     @OA\JsonContent(example="{
    ""qrCodeData"":""SOME-QR-CODE-DATA"",
    ""qrCodeUrl"":""URL-WITH-QR-CODE-IMAGE""
}")
     * )
     * @OA\Response(
     *     response=400,
     *     description="Returns type, title and additional information about the error",
     *     @OA\JsonContent(example="{
    ""type"": ""ticket_creation_error"",
    ""title"": ""There was an error during ticket creation"",
    ""errorMessage"": ""Some additional information about the error""
}")
     * )
     * @OA\Tag(name="import")
     */
    public function import(
        Request $request,
        JsonConverter $converter,
        TransactionProcessor $transactionProcessor,
        TicketProcessor $ticketProcessor
    ): Response
    {
        $content = $request->getContent();
        $transaction = $converter->getTransaction($content);

        $transactionProcessor->addToLog($transaction);

        $transactionId = $transaction->getTicketNr();
        $ticketData = $ticketProcessor->getTicketData($transaction);

        $this->getDoctrine()
            ->getRepository(DistribusionTransactionLog::class)
            ->markLogProcessed($transactionId);

        return new JsonResponse($ticketData);
    }

    /**
     * @Route("/vendor/check/29", methods={"POST"}, name="distribusion_vendor_check")
     * @Route("/distribusion/connections/check", methods={"POST"}, name="disribusion_connections_check")
     * @param Request $request
     * @param JsonConverter $converter
     * @param BookingProcessor $bookingProcessor
     * @param RoundTripBookingProcessor $roundTripBookingProcessor
     * @return JsonResponse
     * @throws \Exception
     */
    public function checkConnections(
        Request $request,
        JsonConverter $converter,
        BookingProcessor $bookingProcessor,
        RoundTripBookingProcessor $roundTripBookingProcessor
    ): Response
    {
        $connectionsData = [];
        $content = $request->getContent();
        $bookings = $converter->getBookings($content);

        $isRoundTrip = count($bookings) > 1;

        if($roundTripBookingProcessor->isMarketingCarrierWithRoundTripFareSupport($bookings[0])) {
            //TODO RoundTripBookingProcessor should replace BookingProcessor in the end

            $connectionsDataResponse = $roundTripBookingProcessor->retry('getConnectionData', $bookings, $isRoundTrip, 1);

            if (!$roundTripBookingProcessor->isConnectionExist($connectionsDataResponse)) {
                throw new ApiException((new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)));
            }

            foreach ($bookings as $booking) {
                $booking->setExtraData($roundTripBookingProcessor->getConnectionExtraData($connectionsDataResponse, $booking));
            }

            $vacancyDataResponse = $roundTripBookingProcessor->retry('getVacancyData', $bookings, $isRoundTrip);

            if (!$vacancyDataResponse['data']['attributes']['vacant']) {
                throw new ApiException((new ApiProblem(ApiProblem::TYPE_AVAILABILITY_ERROR)));
            }

            $connectionsData[] = $connectionsDataResponse['data'][0];
            return new JsonResponse($connectionsData);
        }

        /* @var Booking $booking */
        foreach ($bookings as $booking) {
            $bookingProcessor->convertInternalCodes($booking);

            $connectionData = $bookingProcessor->retry('getConnectionData', $booking, 1);

            if (!$bookingProcessor->isConnectionExist($connectionData)) {
                throw new ApiException((new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)));
            }

            $booking->setExtraData($bookingProcessor->getExtraData($connectionData));

            $vacancyData = $bookingProcessor->retry('getVacancyData', $booking);

//            if ($vacancyData['data']['attributes']['total_price'] != $booking->getPrice()) {
//                throw new ApiException((new ApiProblem(ApiProblem::TYPE_PRICE_DISCREPANCY_ERROR)));
//            }

            if (!$vacancyData['data']['attributes']['vacant']) {

                throw new ApiException((new ApiProblem(ApiProblem::TYPE_AVAILABILITY_ERROR)));
            }

            $connectionsData[] = $connectionData['data'][0];
        }

        return new JsonResponse($connectionsData);
    }

    /**
     * @Route("/distribusion/schedule/{departureCode}/{arrivalCode}/{date}", methods={"GET"}, name="get_distribusion_schedule")
     * @throws \Exception
     */
    public function getSchedule(
        int $departureCode,
        int $arrivalCode,
        string $date,
        BookingProcessor $bookingProcessor,
        MicroserviceLogger $logger
    ): Response
    {
        $schedule = new Schedule();
        $schedule->setDepartureStationId($departureCode);
        $schedule->setArrivalStationId($arrivalCode);
        $schedule->setDate($date);

        $bookingProcessor->convertInternalCodes($schedule);
        $scheduleData = $bookingProcessor->getScheduleData($schedule);

        return new JsonResponse($scheduleData);
    }

    /**
     * @Route("/distribusion/download/{ticketId}", methods={"GET"}, name="get_distribusion_download_ticket")
     * @throws \Exception
     */
    public function getTicket(string $ticketId): Response
    {
        $file = $this->getParameter('kernel.project_dir') . '/public/distribusion/tickets/'.$ticketId.'.pdf';

        if(!file_exists($file)){
            return new JsonResponse(
                'Ticket not found',
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return new BinaryFileResponse($file, JsonResponse::HTTP_OK, ['Content-Disposition' => 'attachment']);
    }

    /**
     * @Route("/distribusion/price", methods={"POST"}, name="disribusion_schedule_price")
     *
     * @param Request $request
     * @param JsonConverter $converter
     * @param BookingProcessor $bookingProcessor
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getPrice(Request $request, JsonConverter $converter, BookingProcessor $bookingProcessor)
    {
        try {
            $content = $request->getContent();

            $price = $converter->getPrice($content);

            $bookingProcessor->convertInternalCodes($price);

            $connectionData = $bookingProcessor->getConnectionData($price);

            $price->setExtraData($bookingProcessor->getExtraData($connectionData));

            $passengersData = [
                'adults',
                'children',
                'infants'
            ];

            $priceData = [];
            foreach($passengersData as $type) {
                if ((int) $price->{'get' . $type}() !== 0) {
                    $priceData[] = $bookingProcessor->getPriceData($price, $type);
                }
            }

        } catch (\Exception $e) {

            return new JsonResponse($e->getMessage());
        }

        return new JsonResponse(array_sum($priceData));
    }

    /**
     * @Route("/vendor/create/29", methods={"POST"}, name="disribusion_vendor_create")
     * @Route("/distribusion/bookings/create", methods={"POST"}, name="disribusion_bookings_create")
     * @param Request $request
     * @param JsonConverter $converter
     * @param BookingProcessor $bookingProcessor
     * @param RoundTripBookingProcessor $roundTripBookingProcessor
     * @return JsonResponse
     * @throws \Exception
     */
    public function createBookings(
        Request $request,
        JsonConverter $converter,
        BookingProcessor $bookingProcessor,
        RoundTripBookingProcessor $roundTripBookingProcessor): Response
    {
        $ordersData = [];
        $content = $request->getContent();
        $bookings = $converter->getBookings($content);

        $isRoundTrip = count($bookings) > 1;

        if(!empty($ordersData = $roundTripBookingProcessor->createBookings($bookings))){
            return new JsonResponse($ordersData);
        }

        /* @var Booking $booking */
        foreach ($bookings as $booking) {
            if ($bookingLog = $bookingProcessor->getLogByOrderItemId($booking->getOrderItemId())) {
                $ordersData[] = [
                    'orderId' => $bookingLog->getOrderId(),
                    'bookingId' => $bookingLog->getBookingId(),
                    'ticketUrl' => $bookingLog->getTicketUrl()
                ];
            } else {
                $bookingProcessor->convertInternalCodes($booking);
                $connectionData = $bookingProcessor->retry('getConnectionData', $booking);

                if (!$bookingProcessor->isConnectionExist($connectionData)) {
                    throw new ApiException((new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)));
                }

                $booking->setExtraData($bookingProcessor->getExtraData($connectionData));

                $vacancyData = $bookingProcessor->retry('getVacancyData', $booking);

//                if ($vacancyData['data']['attributes']['total_price'] != $booking->getPrice()) {
//                    throw new ApiException((new ApiProblem(ApiProblem::TYPE_PRICE_DISCREPANCY_ERROR)));
//                }

                if (!$vacancyData['data']['attributes']['vacant']) {
                    throw new ApiException((new ApiProblem(ApiProblem::TYPE_AVAILABILITY_ERROR)));
                }

                $ordersData[] = $bookingProcessor->createOrder($booking, $isRoundTrip);
            }

            // round trip and swedish service
            if ($isRoundTrip && substr($booking->getConvertedDepartureStationId(), 0, 2) == "SE") {
                $ordersData[] = $ordersData[0];
                break;
            }
        }

        return new JsonResponse($ordersData);
    }

    /**
     * @IsGranted("ROLE_DISTRIBUSION")
     * @Route("/distribusion/rides", methods={"GET"}, name="distribusion_rides")
     * @OA\Response(
     *     response=200,
     *     description="List of rides available for import",
     *     @OA\JsonContent(example="[
    {
        ""id"": 486,
        ""code"": ""12863-20744"",
        ""service"": ""FIUMICINO(Terravision)"",
        ""status"": ""Enabled"",
        ""route"": ""Fiumicino Airport<->Termini"",
        ""duration"": 55
    }
]")
     * )
     * @OA\Tag(name="rides")
     */
    public function distribusionRides(): Response
    {
        $routesData = [
            [
                "id" => 15,
                "code" => "13887-20846",
                "service" => "BGY",
                "status" => "Enabled",
                "cutoffHh" => 30,
                "route" => "BGY-MIL",
                "duration" => 60,
            ],
            [
                "id" => 16,
                "code" => "20846-13887",
                "service" => "BGY",
                "status" => "Enabled",
                "cutoffHh" => 15,
                "route" => "BGY-MIL",
                "duration" => 60,
            ],
            [
                "id" => 382,
                "code" => "13639-14123",
                "service" => "MPX",
                "status" => "Enabled",
                "cutoffHh" => 15,
                "route" => "MILANO<-> MXP",
                "duration" => 50,
            ],
            [
                "id" => 383,
                "code" => "14123-13639",
                "service" => "MPX",
                "status" => "Enabled",
                "cutoffHh" => 15,
                "route" => "MILANO<-> MXP",
                "duration" => 50
            ],
            [
                "id" => 486,
                "code" => "12863-20744",
                "service" => "FIUMICINO(Terravision)",
                "status" => "Enabled",
                "route" => "Fiumicino Airport<->Termini",
                "duration" => 55,
            ],
            [
                "id" => 487,
                "code" => "20744-12863",
                "service" => "FIUMICINO(Terravision)",
                "status" => "Enabled",
                "route" => "Fiumicino Airport<->Termini",
                "duration" => 45,
            ],
            [
                "id" => 490,
                "code" => "13133-20744",
                "service" => "CIAMPINO(Terravision)",
                "status" => "Enabled",
                "route" => "Ciampino Airport<->Termini",
                "duration" => 45,
            ],
            [
                "id" => 491,
                "code" => "20744-13133",
                "service" => "CIAMPINO(Terravision)",
                "status" => "Enabled",
                "route" => "Ciampino Airport<->Termini",
                "duration" => 40,
            ]
        ];

        return new JsonResponse(
            json_encode($routesData),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/distribusion/rides/data/{vendorCode}", methods={"GET"}, name="distribusion_rides_data")
     * @OA\Parameter(
     *     description="Code of the ride {departureStationId}-{arrivalStationId}",
     *     in="path",
     *     name="vendorCode",
     *     required=true
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Data and information about the ride (stations, route, duration)",
     *     @OA\JsonContent(example="{
    ""id"": 486,
    ""code"": ""12863-20744"",
    ""service"": ""FIUMICINO(Terravision)"",
    ""status"": ""Enabled"",
    ""route"": ""Fiumicino Airport<->Termini"",
    ""duration"": 55,
    ""price"": {
        ""oneWay"": {
            ""EUR"": {
                ""adult"": 7,
                ""child"": 7,
                ""infant"": 0
            },
            ""GBP"": {
                ""adult"": 6,
                ""child"": 6,
                ""infant"": 0
            }
        },
        ""roundTrip"": {
            ""EUR"": {
                ""adult"": 6,
                ""child"": 6,
                ""infant"": 0
            },
            ""GBP"": {
                ""adult"": 6,
                ""child"": 6,
                ""infant"": 0
            }
        }
    }
}")
     * )
     * @OA\Tag(name="rides")
     */
    public function ridesData(
        string $vendorCode,
        RidesProcessor $ridesProcessor,
        Request $request,
        RideCodeGenerator $rideCodeGenerator
    ): Response
    {
        if (is_numeric($vendorCode)) {
            $rideId = $vendorCode;
        } else {
            $rideId = $rideCodeGenerator->getRideIdFromVendorCode($vendorCode);
        }

        return new JsonResponse(
            $rideCodeGenerator->convertRideData(
                $ridesProcessor->getRidesData($rideId)
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/distribusion/rides/schedule/{vendorCode}", methods={"GET"}, name="distribusion_rides_schedule")
     * @OA\Parameter(
     *     description="Code of the ride {departureStationId}-{arrivalStationId}",
     *     in="path",
     *     name="vendorCode",
     *     required=true
     * ),
     * @OA\Parameter(
     *     description="Specify date in 'Y-m-d' format in order to get schedule for the specific date",
     *     in="query",
     *     name="date",
     *     required=false,
     *     @OA\Schema(
     *         type="string",
     *         format="string"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="List of rides (departure time)",
     *     @OA\JsonContent(example="[
    ""07:45"",
    ""08:30"",
    ""11:05"",
    ""11:55"",
    ""14:25"",
    ""15:05"",
    ""15:40"",
    ""18:05"",
    ""18:35"",
    ""20:35"",
    ""21:05""
]")
     * )
     * @OA\Tag(name="rides")
     */
    public function ridesSchedule(
        string $vendorCode,
        RidesProcessor $ridesProcessor,
        Request $request,
        RideCodeGenerator $rideCodeGenerator
    ): Response
    {
        if (is_numeric($vendorCode)) {
            $rideId = $vendorCode;
        } else {
            $rideId = $rideCodeGenerator->getRideIdFromVendorCode($vendorCode);
        }

        return new JsonResponse(
            $ridesProcessor->getSchedule($rideId, $request->query->get('date')),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }
}
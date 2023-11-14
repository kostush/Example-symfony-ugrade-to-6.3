<?php

namespace App\Controller\Api\v1\TerravisionApi;

use App\Controller\Api\BaseApiController;
use App\Service\Schedule\RidesProcessor;
use App\Service\TerravisionApi\ApiProcessor;
use App\Service\TerravisionApi\TerravisionApiProcessor;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @Route("/api/v3")
 */
class TerravisionEticketingApiController extends BaseApiController
{
    const RESOURCE_NAME_TICKET_INFO = 'ticketInfo';
    const RESOURCE_NAME_PASSENGERS_LIST = 'passengersList';

    /**
     * @Route("/validate/ticket/{qrCodeData}/{rideDateTime}", methods={"GET"}, name="retrieve_ticket_info", requirements={"date"=".+"})
     * @OA\Parameter(
     *     description="qrCode data string from ticket purchase operation response",
     *     in="path",
     *     name="qrCodeData",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="QR Code data string"
     *     )
     * ),
     * @OA\Parameter(
     *     description="rideDateTime from schedule for which the ticket is going to be obliterated (format: Y-m-d H:i, e.g 2021-12-01 23:12)",
     *     in="path",
     *     name="date",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="Y-m-d H:i:s"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Returns ticket info if it exsists",
     *     @OA\MediaType(
    mediaType="application/json",
     *             @OA\Schema(
     *                 required={"qrCodeData","qrCodeUrl","purchaseOrderId"},
     *                 @OA\Property(
     *                     property="ticketOwner",
     *                     type="string",
     *                     description="Ticket owner`s Full name"
     *                 ),
     *                 @OA\Property(
     *                     property="sellingDateTime",
     *                     type="string",
     *                     description="Date time when a ticket was sold"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     description="The status of the ticket",
     *                     oneOf={
     *                     	   @OA\Schema(type="string", pattern="issued"),
     *                     	   @OA\Schema(type="string", pattern="obliterated"),
     *                     },
     *                 ),
     *                 @OA\Property(
     *                     property="ticketNumber",
     *                     type="string",
     *                     description="Ticket number"
     *                 ),
     *                 @OA\Property(
     *                     property="directionCode",
     *                     type="string",
     *                     description="Short name of ride direction"
     *                 ),
     *                 @OA\Property(
     *                     property="adults",
     *                     type="int",
     *                     description="Number of adults in the ticket",
     *                 ),
     *                 @OA\Property(
     *                     property="children",
     *                     type="int",
     *                     description="Number of children in the ticket",
     *                 ),
     *                 @OA\Property(
     *                     property="departureBusStop",
     *                     type="string",
     *                     description="Short name of the departure bus stop"
     *                 ),
     *                 @OA\Property(
     *                     property="arrivalBusStop",
     *                     type="string",
     *                     description="Short name of the arrival bus stop"
     *                 ),
     *                 @OA\Property(
     *                     property="rideDateTime",
     *                     type="string",
     *                     description="Date and time of the bus ride in Y-m-d H:i:s format",
     *                     format="Y-m-d H:i:s"
     *                 ),
     *                 @OA\Property(
     *                     property="ticketValidity",
     *                     type="string",
     *                     description="Ticket`s validity status",
     *                     oneOf={
     *                     	   @OA\Schema(type="string", pattern="not_valid", description="Ticket can not be obliterated"),
     *                     	   @OA\Schema(type="string", pattern="valid", description="Ticket can be obliterated"),
     *                         @OA\Schema(type="string", pattern="can_not_be_obliterated_for_this_time", description="Ticket can not be obliterated for today`s ride which is in the past, but tiket still can be obliterated within today for other future rides"),
     *                     },
     *                 ),
     *                 example=    {
    "ticketOwner": "Ahmed  Nail",
    "sellingDateTime": "2022-07-19T16:44:24+0100",
    "status": "issued",
    "ticketNumber": "TRVBUS00008883077",
    "directionCode": "Fiumicino airport-Termini",
    "adults": 1,
    "children": 0,
    "departureBusStop": "FIUMICINO AIRPORT",
    "arrivalBusStop": "TERMINI STATION ROME",
    "rideDateTime": "2022-07-20T08:30:00+0100",
    "ticketValidity": "can_not_be_obliterated_for_this_time"
    }
     *             )
     *     ))
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(example="
    ""Invalid date format""
    ")
     *),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized request",
     *     @OA\JsonContent(example="
    ""Unauthorized""
    ")
     *),
     * @OA\Response(
     *     response=404,
     *     description="Resourse not found",
     *     @OA\JsonContent(example="
    ""ticketInfo not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Terravision e-ticketing")
     */
    public function validateTicket(string $qrCodeData, string $rideDateTime, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->validateTicket($qrCodeData, $rideDateTime),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_DIRECTION);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/obliterate/ticket/{qrCodeData}/{rideDateTime}", methods={"PATCH"}, name="obliterate_ticket", requirements={"date"=".+"})
     * @OA\Parameter(
     *     description="qrCode data string from ticket purchase operation response",
     *     in="path",
     *     name="qrCodeData",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="QR Code data string"
     *     )
     * ),
     * @OA\Parameter(
     *     description="rideDateTime from schedule for which the ticket is going to be obliterated (format: Y-m-d H:i, e.g 2021-12-01 23:12)",
     *     in="path",
     *     name="rideDateTime",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="Y-m-d H:i:s"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Returns the qrCodeData, qrCodeUrl and purchaseOrderId if the ticket was successfuly created. Also returns returnQrCode , returnQrCodeUrl and returnPurchaseOrderId if its roundtrip",
     *     @OA\MediaType(
    mediaType="application/json",
     *             @OA\Schema(
     *                 example=
    "Ticket successfully obliterated"

     *             )
     *     ))
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(example="
    ""Invalid date format""
    ")
     *),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized request",
     *     @OA\JsonContent(example="
    ""Unauthorized""
    ")
     *),
     * @OA\Response(
     *     response=404,
     *     description="Resourse not found",
     *     @OA\JsonContent(example="
    ""ticketInfo not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Terravision e-ticketing")
     */
    public function obliterateTicket(string $qrCodeData, string $rideDateTime, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->obliterateTicket($qrCodeData, $rideDateTime),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_TICKET_INFO);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/passengers/{directionId}/{rideDateTime}", methods={"GET"}, name="passengers_list", requirements={"directionId"="\d+", "rideDateTime"=".+"})
     * @OA\Parameter(
     *     description="ID of the direction to retrieve a ride passengers list",
     *     in="path",
     *     name="directionId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Parameter(
     *     description="rideDateTime from schedule for which the passengers list is going to be retrieved (format: Y-m-d H:i, e.g 2021-12-01 23:12)",
     *     in="path",
     *     name="rideDateTime",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="Y-m-d H:i:s"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Passengers list for the given ride datetime and direction",
     *     @OA\JsonContent(example="
    [
        {
            ""ticketNumber"": ""TRVBUS00008999111"",
            ""fullName"": ""Paula Gormley (2)""
        },
        {
            ""ticketNumber"": ""TRVBUS00009017593"",
            ""fullName"": ""Aoife Austin (4)""
        },
        {
            ""ticketNumber"": ""TRVBUS00009023852"",
            ""fullName"": ""Lyubov Bondarenko (2)*""
        }
    ]
    ")
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(example="
    ""Invalid date format""
    ")
     *),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized request",
     *     @OA\JsonContent(example="
    ""Unauthorized""
    ")
     *),
     * @OA\Response(
     *     response=404,
     *     description="Resourse not found",
     *     @OA\JsonContent(example="
    ""direction not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Terravision e-ticketing")
     */
    public function passengersList(int $directionId, string $rideDateTime, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getPassengersList($directionId, $rideDateTime),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_PASSENGERS_LIST);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/ticket/encrypted/{encryptedToken}", methods={"GET"}, name="download_encrypted_ticket")
     * @OA\Parameter(
     *     description="ID of the encryptedToken",
     *     in="path",
     *     name="encryptedToken",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="string"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Download Pdf ticket"
     * ),
     * * @OA\Response(
     *     response=400,
     *     description="More than one attempt to download the ticket",
     *     @OA\JsonContent(example="
    ""Ticket has been already downloaded""
    ")
     *),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized request",
     *     @OA\JsonContent(example="
    ""Unauthorized""
    ")
     *),
     * @OA\Response(
     *     response=404,
     *     description="Resourse not found",
     *     @OA\JsonContent(example="
    ""order not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Terravision e-ticketing")
     */
    public function downloadTicketByEncryptedToken(string $encryptedToken, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new Response(
                $apiProcessor->downloadTicketByEncryptedToken($encryptedToken),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('attachment; filename="%s.pdf"', time())
                ]
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_ORDER);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/purchase/ticket/staff", methods={"POST"}, name="purchase_ticket_by_staff")
     * @OA\RequestBody (
     *     request="order",
     *     description="Send a request with JSON data, that includes all neccesary information about the bus ride and purchase.",
     *     @OA\MediaType(
    mediaType="application/json",
     *             @OA\Schema(
     *                 required={"currency","price","adults","children","infants","rideDateTime","fromStopId","toStopId","firstName","lastName","transactionId"},
     *                 @OA\Property(
     *                     property="currency",
     *                     oneOf={
     *                     	   @OA\Schema(type="string", pattern="EUR"),
     *                     	   @OA\Schema(type="string", pattern="GBR"),
     *                     },
     *                 ),
     *                 @OA\Property(
     *                     property="price",
     *                     type="float",
     *                     description="The (float) total price for which the ticket is going to be sold e.g. If there are 2 adults for 7 EUR per each the price would be 14",
     *                 ),
     *                 @OA\Property(
     *                     property="adults",
     *                     type="int",
     *                     description="Number of adults in the ticket",
     *                 ),
     *                 @OA\Property(
     *                     property="children",
     *                     type="int",
     *                     description="Number of children in the ticket",
     *                 ),
     *                 @OA\Property(
     *                     property="infants",
     *                     type="int",
     *                     description="Number of infants in the ticket",
     *                 ),
     *                 @OA\Property(
     *                     property="rideDateTime",
     *                     type="string",
     *                     description="Date and time of the bus ride in Y-m-d H:i format",
     *                     format="Y-m-d H:i"
     *                 ),
     *                 @OA\Property(
     *                     property="returnRideDateTime",
     *                     type="string",
     *                     description="Return Date and time of the bus ride in Y-m-d H:i format",
     *                     format="Y-m-d H:i"
     *                 ),
     *                 @OA\Property(
     *                     property="fromStopId",
     *                     type="int",
     *                     description="Id of the departure bus stop"
     *                 ),
     *                 @OA\Property(
     *                     property="toStopId",
     *                     type="int",
     *                     description="Id of the arrival bus stop"
     *                 ),
     *                 @OA\Property(
     *                     property="firstName",
     *                     type="string",
     *                     description="First name of the customer"
     *                 ),
     *                 @OA\Property(
     *                     property="lastName",
     *                     type="string",
     *                     description="Last name of the customer"
     *                 ),
     *                 @OA\Property(
     *                     property="transactionId",
     *                     type="string",
     *                     description="Unique identifier for further syncronization purposes of the purchase"
     *                 ),
     *                 example={
    "currency":"EUR",
    "price": 7.6,
    "adults": 7,
    "children": 9,
    "infants": 1,
    "rideDateTime": "2022-02-02 13:05",
    "returnRideDateTime": "2022-02-12 11:05",
    "fromStopId": 787,
    "toStopId": 934,
    "firstName": "test",
    "lastName": "test",
    "transactionId": "asdsahgd3453gdfg"
    }
     *             )
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Returns encryptedToken for download of a ticket",
     *     @OA\MediaType(
    mediaType="application/json",
     *             @OA\Schema(
     *                 required={"encryptedToken"},
     *                 @OA\Property(
     *                     property="encryptedToken",
     *                     type="string",
     *                     description="encryptedToken for download of a ticket"
     *                 ),
     *                 example={
    "encryptedToken":"SOME-encryptedToken-DATA"
    }
     *             )
     *     ))
     * )
     * @OA\Response(
     *     response=400,
     *     description="Returns type, title and additional information about the error",
     *     @OA\JsonContent(example="This value should not be blank. children")
     * ),
     * @OA\Response(
     *     response=403,
     *     description="Can be caused by multiple reasons e.g. given credentials don`t have the proper permission level",
     *     @OA\JsonContent(example="Forbidden")
     * ),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="Server error"
    )
     *),
     * @OA\Tag(name="Purchase terravision ticket")
     */
    public function purchaseTicketByStaff(Request $request, TerravisionApiProcessor $apiProcessor): Response
    {
        $content = $request->getContent();
        try {
            return new JsonResponse(
                $apiProcessor->purchaseTicketByStaff($content),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {
            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_DIRECTION);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/schedule/{directionId}/{date}", methods={"GET"}, name="eschedule_list", requirements={"directionId"="\d+", "date"=".+"})
     * @OA\Parameter(
     *     description="ID of the direction to retrieve the schedule",
     *     in="path",
     *     name="directionId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Parameter(
     *     description="Date for which the schedule will be retrieved (format: Y-m-d, e.g 2021-12-01)",
     *     in="path",
     *     name="date",
     *     required=true,
     *     @OA\Schema(
     *         type="string",
     *         format="Y-m-d"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Schedule of the rides for the given date and direction",
     *     @OA\JsonContent(
     *      type="object",
     *         example={
     *         "2023-06-14 08:30:00": "Jun-14 08:30:00",
     *         "2023-06-14 09:10:00": "Jun-14 09:10:00",
     *         "2023-06-14 09:45:00": "Jun-14 09:45:00",
     *         "2023-06-14 10:00:00": "Jun-14 10:00:00",
     *         "2023-06-14 10:25:00": "Jun-14 10:25:00",
     *         "2023-06-14 11:05:00": "Jun-14 11:05:00",
     *         "2023-06-14 11:55:00": "Jun-14 11:55:00",
     *         "2023-06-14 12:35:00": "Jun-14 12:35:00",
     *         "2023-06-14 13:00:00": "Jun-14 13:00:00",
     *         "2023-06-14 13:20:00": "Jun-14 13:20:00",
     *         "2023-06-14 13:45:00": "Jun-14 13:45:00",
     *         "2023-06-14 14:25:00": "Jun-14 14:25:00",
     *         "2023-06-14 15:05:00": "Jun-14 15:05:00",
     *         "2023-06-14 15:40:00": "Jun-14 15:40:00",
     *         "2023-06-14 16:10:00": "Jun-14 16:10:00",
     *         "2023-06-14 16:40:00": "Jun-14 16:40:00",
     *         "2023-06-14 17:15:00": "Jun-14 17:15:00",
     *         "2023-06-14 18:05:00": "Jun-14 18:05:00",
     *         "2023-06-14 18:35:00": "Jun-14 18:35:00",
     *         "2023-06-14 19:20:00": "Jun-14 19:20:00",
     *         "2023-06-14 19:45:00": "Jun-14 19:45:00",
     *         "2023-06-14 20:35:00": "Jun-14 20:35:00",
     *         "2023-06-14 21:05:00": "Jun-14 21:05:00",
     *         "2023-06-14 21:50:00": "Jun-14 21:50:00",
     *         "2023-06-14 23:00:00": "Jun-14 23:00:00",
     *         "2023-06-15 00:30:00": "Jun-15 00:30:00"
     *         }
     *       )
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Bad request",
     *     @OA\JsonContent(example="
    ""Invalid date format""
    ")
     *),
     * @OA\Response(
     *     response=401,
     *     description="Unauthorized request",
     *     @OA\JsonContent(example="
    ""Unauthorized""
    ")
     *),
     * @OA\Response(
     *     response=404,
     *     description="Resourse not found",
     *     @OA\JsonContent(example="
    ""direction not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Terravision e-ticketing")
     */
    public function eSchedule(int $directionId, string $date, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getSchedule($directionId, $date, $eTicket = true),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_PASSENGERS_LIST);
            return $this->getErrorResponse($responseData);
        }
    }
}
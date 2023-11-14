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
 * @Route("/api/v2")
 */
class TerravisionApiController extends BaseApiController
{
        /**
     * @Route("/cities", methods={"GET"}, name="cities_list")
     * @OA\Response(
     *     response=200,
     *     description="Data and information about the city (cityId, city name, available services)",
     *     @OA\JsonContent(example="{
    ""cities"": [
        {
            ""cityId"": 48,
            ""cityName"": ""ROME"",
        },
        {
            ""cityId"": 48,
            ""cityName"": ""ROME"",
        }
    ]
}")
     * )
     * @OA\Tag(name="Retrieve terravision data")
     */
    public function cities(TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getCities(),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_CITIES);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/city/{cityId}", methods={"GET"}, name="city_data", requirements={"cityId"="\d+"})
     * @OA\Parameter(
     *     description="ID of the city",
     *     in="path",
     *     name="cityId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Data and information about the city (cityId, city name, available services)",
     *     @OA\JsonContent(example="{
    ""cityId"": 48,
    ""cityName"": ""ROME"",
    ""services"": [
        {
            ""serviceId"": 8,
            ""serviceCode"": ""FIUMICINO(Terravision)""
        },
        {
            ""serviceId"": 10,
            ""serviceCode"": ""CIAMPINO(Terravision)""
        }
    ]
}")
     * ),
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
    ""city not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Retrieve terravision data")
     */
    public function cityData(int $cityId, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getCityData($cityId),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_CITY);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/service/{serviceId}", methods={"GET"}, name="service_data", requirements={"serviceId"="\d+"})
     * @OA\Parameter(
     *     description="ID of the service",
     *     in="path",
     *     name="serviceId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Data and information about the service (service name, service description, city, preferred currency, available dirrections)",
     *     @OA\JsonContent(example="{
    ""serviceId"": 48,
    ""serviceCode"": ""CIA"",
    ""serviceName"": ""Ciampino(Terravision)"",
    ""serviceDescription"": ""The service connects Ciampino airport to Rome city centre (Termini station)."",
    ""city"": ""Rome""
    ""currency"": ""EUR""
    ""directions"": [
        {
            ""directionId"": 8,
            ""directionCode"": ""Ciampino airport - city center""
        },
        {
            ""directionId"": 10,
            ""directionCode"": ""Fiumicino airport-Termini""
        }
    ]
}")
     * ),
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
    ""service not found""
    ")
     *),
     * @OA\Response(
     *     response=500,
     *     description="Server error",
     *     @OA\JsonContent(example="
    ""Server error""
    ")
     *),
     * @OA\Tag(name="Retrieve terravision data")
     */
    public function serviceData(int $serviceId, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getSeviceData($serviceId),
                JsonResponse::HTTP_OK,
                [],
                true
            );
        } catch (\Throwable $e) {

            $responseData = $this->handleResponseException($e, self::RESOURCE_NAME_SERVICE);
            return $this->getErrorResponse($responseData);
        }
    }

    /**
     * @Route("/direction/{directionId}", methods={"GET"}, name="direction_data", requirements={"directionId"="\d+"})
     * @OA\Parameter(
     *     description="ID of the direction from one stop to another",
     *     in="path",
     *     name="directionId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Data and information about the direction (service name, route, duration, prices in different currencies)",
     *     @OA\JsonContent(example="{
    ""directionId"": 486,
    ""directionCode"": ""Dublin Airport city center"",
    ""serviceName"": ""Dublin Airport"",
    ""duration"": 55,
    ""from"": {
        ""fromStopId"": 787,
        ""fromStopName"": ""Dublin city centre (all stops)""
    },
    ""to"": {
        ""toStopId"": 934,
        ""toStopName"": ""Dublin Airport: Outside the terminal T1 & T2""
    }
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
                ""adult"": 10,
                ""child"": 5,
                ""infant"": 0
            },
            ""GBP"": {
                ""adult"": 8,
                ""child"": 4,
                ""infant"": 0
            }
        }
    }
  }")
     * ),
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
     * @OA\Tag(name="Retrieve terravision data")
     */
    public function directionData(int $directionId, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getDirectionData($directionId),
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
     * @Route("/schedule/{directionId}/{date}", methods={"GET"}, name="schedule_list", requirements={"directionId"="\d+", "date"=".+"})
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
     *     @OA\JsonContent(example="
    [
        ""05:50"",
        ""06:50"",
        ""07:50"",
        ""08:50"",
        ""09:50"",
        ""10:50"",
        ""11:50"",
        ""12:50"",
        ""13:50"",
        ""14:50"",
        ""15:50"",
        ""16:50"",
        ""17:50"",
        ""18:50"",
        ""19:50"",
        ""20:50"",
        ""21:50"",
        ""22:50"",
        ""23:50""
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
     * @OA\Tag(name="Retrieve terravision data")
     */
    public function schedule(int $directionId, string $date, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new JsonResponse(
                $apiProcessor->getSchedule($directionId, $date),
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
     * @Route("/purchase/ticket", methods={"POST"}, name="purchase_ticket")
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
     *     description="Returns the qrCodeData, qrCodeUrl and purchaseOrderId if the ticket was successfuly created. Also returns returnQrCode , returnQrCodeUrl and returnPurchaseOrderId if its roundtrip",
     *     @OA\MediaType(
    mediaType="application/json",
     *             @OA\Schema(
     *                 required={"qrCodeData","qrCodeUrl","purchaseOrderId"},
     *                 @OA\Property(
     *                     property="qrCodeData",
     *                     type="string",
     *                     description="Some data to be converted into a QR-CODE"
     *                 ),
     *                 @OA\Property(
     *                     property="qrCodeUrl",
     *                     type="string",
     *                     description="Url of qr code image"
     *                 ),
     *                 @OA\Property(
     *                     property="purchaseOrderId",
     *                     type="string",
     *                     description="Purchase order identifier"
     *                 ),
     *                 @OA\Property(
     *                     property="returnQrCode",
     *                     type="string",
     *                     description="Some data to be converted into a QR-CODE for return trip"
     *                 ),
     *                 @OA\Property(
     *                     property="returnQrCodeUrl",
     *                     type="string",
     *                     description="Url of qr code image for return trip"
     *                 ),
     *                 @OA\Property(
     *                     property="returnPurchaseOrderId",
     *                     type="string",
     *                     description="Purchase order identifier of return trip"
     *                 ),
     *                 example={
    "qrCodeData":"SOME-QR-CODE-DATA",
    "qrCodeUrl":"URL-WITH-QR-CODE-IMAGE",
    "purchaseOrderId":"2698745",
    "returnQrCode":"SOME-QR-CODE-DATA for return trip",
    "returnQrCodeUrl":"URL-WITH-QR-CODE-IMAGE  for return trip",
    "returnPurchaseOrderId":"2698746"
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
    public function purchaseTicket(Request $request, TerravisionApiProcessor $apiProcessor): Response
    {
        $content = $request->getContent();
        try {
            return new JsonResponse(
                $apiProcessor->purchaseTicket($content),
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
     * @Route("/ticket/{purchaseOrderId}", methods={"GET"}, name="download_ticket")
     * @OA\Parameter(
     *     description="ID of the purchaseOrder",
     *     in="path",
     *     name="purchaseOrderId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Download Pdf ticket"
     * ),
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
     * @OA\Tag(name="Retrieve terravision data")
     */
    public function downloadTicket(int $purchaseOrderId, TerravisionApiProcessor $apiProcessor): Response
    {
        try {
            return new Response(
                $apiProcessor->downloadTicket($purchaseOrderId),
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
}
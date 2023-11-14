<?php

namespace App\Controller\Api\v1;

use App\Controller\Api\BaseApiController;
use App\Service\Schedule\RidesProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
 * @Route("/api/v1/rides")
 */
class ScheduleController extends BaseApiController
{
    /**
     * @Route("", methods={"GET"}, name="terravision_rides")
     * @OA\Response(
     *     response=200,
     *     description="List of rides available for import",
     *     @OA\JsonContent(example="[
    {
        ""id"": 486,
        ""code"": ""Fiumicino airport-Termini"",
        ""service"": ""FIUMICINO(Terravision)"",
        ""status"": ""Enabled"",
        ""route"": ""Fiumicino Airport<->Termini"",
        ""duration"": 55
    }
]")
     * )
     * @OA\Tag(name="rides")
     */
    public function rides()
    {
        $routesData = [
            [
                "id" => 15,
                "code" => "BGY-MILCEN",
                "service" => "BGY",
                "status" => "Enabled",
                "cutoffHh" => 30,
                "route" => "BGY-MIL",
                "duration" => 60,
            ],
            [
                "id" => 16,
                "code" => "MILCEN-BGY",
                "service" => "BGY",
                "status" => "Enabled",
                "cutoffHh" => 15,
                "route" => "BGY-MIL",
                "duration" => 60,
            ],
            [
                "id" => 382,
                "code" => "MPX-MILANOCENT",
                "service" => "MPX",
                "status" => "Enabled",
                "cutoffHh" => 15,
                "route" => "MILANO<-> MXP",
                "duration" => 50,
            ],
            [
                "id" => 383,
                "code" => "MILANOCENTR-MPX",
                "service" => "MPX",
                "status" => "Enabled",
                "cutoffHh" => 15,
                "route" => "MILANO<-> MXP",
                "duration" => 50
            ],
            [
                "id" => 486,
                "code" => "Fiumicino airport-Termini",
                "service" => "FIUMICINO(Terravision)",
                "status" => "Enabled",
                "route" => "Fiumicino Airport<->Termini",
                "duration" => 55,
            ],
            [
                "id" => 487,
                "code" => "Termini-Fiumicino airport",
                "service" => "FIUMICINO(Terravision)",
                "status" => "Enabled",
                "route" => "Fiumicino Airport<->Termini",
                "duration" => 45,
            ],
            [
                "id" => 490,
                "code" => "Ciampino airport-Termini",
                "service" => "CIAMPINO(Terravision)",
                "status" => "Enabled",
                "route" => "Ciampino Airport<->Termini",
                "duration" => 45,
            ],
            [
                "id" => 491,
                "code" => "Termini-Ciampino airport",
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
     * @Route("/{rideId}", methods={"GET"}, name="rides_schedule", requirements={"rideId"="\d+"})
     * @OA\Parameter(
     *     description="ID of the ride",
     *     in="path",
     *     name="rideId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
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
    public function ridesSchedule(int $rideId, RidesProcessor $ridesProcessor, Request $request)
    {
        return new JsonResponse(
            $ridesProcessor->getSchedule($rideId, $request->query->get('date')),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @Route("/data/{rideId}", methods={"GET"}, name="rides_data", requirements={"rideId"="\d+"})
     * @OA\Parameter(
     *     description="ID of the ride",
     *     in="path",
     *     name="rideId",
     *     required=true,
     *     @OA\Schema(
     *         type="integer",
     *         format="int"
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Data and information about the ride (stations, route, duration)",
     *     @OA\JsonContent(example="{
    ""id"": 486,
    ""code"": ""Fiumicino airport-Termini"",
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
    public function ridesData(int $rideId, RidesProcessor $ridesProcessor, Request $request)
    {
        return new JsonResponse(
            $ridesProcessor->getRidesData($rideId),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }
}
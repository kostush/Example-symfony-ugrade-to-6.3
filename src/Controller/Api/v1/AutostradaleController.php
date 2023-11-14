<?php


namespace App\Controller\Api\v1;


use App\Controller\Api\BaseApiController;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Service\Autostradale\BookingProcessor;
use App\Service\Autostradale\JsonConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @todo add basic auth
 * @Route("/api/v1")
 */
class AutostradaleController extends BaseApiController
{
    /**
     * @Route("/vendor/check/22", methods={"POST"}, name="autostradale_vendor_check")
     */
    public function checkAvailability(Request $request, JsonConverter $converter, BookingProcessor $processor)
    {
        $content = $request->getContent();
        $bookings = $converter->getBookings($content);

        if (count($bookings) === 1) { // one way trip
            $processor->checkAvailability($bookings[0]);
        } elseif (count($bookings) === 2) { // round trip
            $processor->checkAvailability($bookings[0], $bookings[1]);
        } else {
            $errorMessage = !count($bookings) ? 'No bookings in the request' : 'Too many bookings in the request';
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_VALIDATION_ERROR))->set('errorMessage', $errorMessage));
        }

        return new JsonResponse('ok');
    }

    /**
     * @Route("/vendor/create/22", methods={"POST"}, name="autostradale_vendor_create")
     */
    public function createBookings(Request $request, JsonConverter $converter, BookingProcessor $processor)
    {
        $content = $request->getContent();
        $bookings = $converter->getBookings($content);

        if (count($bookings) === 1) { // one way trip
            $departureBooking = $bookings[0];
            $responseData = $processor->createBookings($departureBooking);
            return new JsonResponse([$responseData]);
        } elseif (count($bookings) === 2) { // round trip
            list($departureBooking, $returnBooking) = $bookings;
            $responseData = $processor->createBookings($departureBooking, $returnBooking);
            return new JsonResponse([$responseData, $responseData]);
        } else {
            $errorMessage = !count($bookings) ? 'No bookings in the request' : 'Too many bookings in the request';
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_VALIDATION_ERROR))->set('errorMessage', $errorMessage));
        }
    }

    /**
     * @Route("/vendor/update/22", methods={"POST"}, name="autostradale_vendor_update")
     */
    public function updateBooking(Request $request, JsonConverter $converter, BookingProcessor $processor)
    {
        $content = $request->getContent();
        $bookingUpdate = $converter->getBookingUpdate($content);

        $responseData = $processor->updateBooking($bookingUpdate);

        return new JsonResponse($responseData);
    }
}
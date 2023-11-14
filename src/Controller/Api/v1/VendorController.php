<?php

namespace App\Controller\Api\v1;

use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Service\Distribusion\BookingProcessor as DistribusionBookingProcessor;
use App\Service\Autostradale\LoggerProcessor as AutostradaleLoggerProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1")
 */
class VendorController
{
    /**
     * @Route("/vendor/ticket/{orderItemId}", methods={"GET"}, name="vendor_ticket")
     */
    public function orderItemTicket($orderItemId, DistribusionBookingProcessor $distribusionBookingProcessor, AutostradaleLoggerProcessor $autostradaleLoggerProcessor)
    {
        $ticketUrl = $distribusionBookingProcessor->findTicketUrl($orderItemId);

        if (!$ticketUrl) {
            $ticketUrl = $autostradaleLoggerProcessor->findTicketUrl($orderItemId);
        }

        if (!$ticketUrl) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR)));
        }

        return new JsonResponse(['ticketUrl' => $ticketUrl]);
    }

    /**
     * @Route("/vendor/ticket/22/{orderItemId}", methods={"GET"}, name="vendor_22_ticket")
     */
    public function orderItemTicketAutostradale($orderItemId, AutostradaleLoggerProcessor $autostradaleLoggerProcessor)
    {
        $ticketUrl = $autostradaleLoggerProcessor->findTicketUrl($orderItemId);

        if (!$ticketUrl) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR)));
        }

        return new JsonResponse(['ticketUrl' => $ticketUrl]);
    }

    /**
     * @Route("/vendor/ticket/29/{orderItemId}", methods={"GET"}, name="vendor_29_ticket")
     */
    public function orderItemTicketDistribusion($orderItemId, DistribusionBookingProcessor $distribusionBookingProcessor)
    {
        $ticketUrl = $distribusionBookingProcessor->findTicketUrl($orderItemId);

        if (!$ticketUrl) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR)));
        }

        return new JsonResponse(['ticketUrl' => $ticketUrl]);
    }
}
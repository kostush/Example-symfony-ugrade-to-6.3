<?php

namespace App\Controller\Api\v1;

use App\Controller\Api\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/v1")
 */
class TransactionController extends BaseApiController
{
    /**
     * @Route("/test", methods={"POST"})
     */
    public function test()
    {
        return new JsonResponse([
            'qrCode' => 'this is QR code'
        ]);
    }
}
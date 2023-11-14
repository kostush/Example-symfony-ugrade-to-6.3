<?php

namespace App\Controller\Api;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class BaseApiController extends BaseController
{
    const RESPONSE_DATA_MESSAGE_KEY_NAME = 'message';
    const RESPONSE_DATA_RESPONSE_CODE_KEY_NAME = 'responseCode';

    const RESOURCE_NAME_DIRECTION = 'direction';
    const RESOURCE_NAME_CITIES = 'cities';
    const RESOURCE_NAME_CITY = 'city';
    const RESOURCE_NAME_SERVICE = 'service';
    const RESOURCE_NAME_ORDER = 'order';

    /**
     * @param \Throwable $e
     * @param string $resourceName
     * @return array
     */
    protected function handleResponseException(\Throwable $e, string $resourceName): array
    {
        $response = $this->getResponse("Server error", Response::HTTP_INTERNAL_SERVER_ERROR);

        if ($e->getCode() == Response::HTTP_NOT_FOUND) {

            $response = $this->getResponse("not found", Response::HTTP_NOT_FOUND, $resourceName);

        } elseif ($e instanceof UnauthorizedHttpException || $e->getCode() == Response::HTTP_UNAUTHORIZED) {

            $response = $this->getResponse("Unauthorized", Response::HTTP_UNAUTHORIZED);

        } elseif ($e instanceof BadRequestHttpException){

            $response = $this->getResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);

        } elseif ($e->getCode() == Response::HTTP_FORBIDDEN){

            $response = $this->getResponse('Forbidden', Response::HTTP_FORBIDDEN);
        } elseif ($e->getCode() == Response::HTTP_PRECONDITION_FAILED){

            $response = $this->getResponse('Bad request', Response::HTTP_BAD_REQUEST);
        }

        return $response;
    }

    /**
     * @param array $responseData
     * @return JsonResponse
     */
    protected function getErrorResponse(array $responseData): JsonResponse
    {
        return new JsonResponse(
            $responseData[self::RESPONSE_DATA_MESSAGE_KEY_NAME],
            $responseData[self::RESPONSE_DATA_RESPONSE_CODE_KEY_NAME]
        );
    }

    /**
     * @param string $message
     * @param string $httpCode
     * @param string|null $resourceName
     * @return array
     */
    private function getResponse(string $message, string $httpCode, ?string $resourceName = null)
    {
        $message = $resourceName ? $resourceName . ' ' . $message : $message;
        return [
            self::RESPONSE_DATA_MESSAGE_KEY_NAME => $message,
            self::RESPONSE_DATA_RESPONSE_CODE_KEY_NAME => $httpCode
        ];
    }
}
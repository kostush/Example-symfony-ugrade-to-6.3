<?php

namespace App\Controller\Api\v1;

use App\Controller\Api\BaseApiController;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Service\EmailNotifier;
use App\Service\GoEuro\JsonConverter;
use App\Service\GoEuro\TicketProcessor;
use App\Service\GoEuro\TransactionProcessor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/api/v1/omio")
 */
class OmioController extends BaseApiController
{
    /**
     * @IsGranted("ROLE_OMIO")
     * @Route("/import", methods={"POST"}, name="omio_import")
     */
    public function import(Request $request, JsonConverter $converter, TransactionProcessor $transactionProcessor, TicketProcessor $ticketProcessor, EmailNotifier $emailNotifier)
    {
        $content = $request->getContent();
        $transaction = $converter->convertTransaction($content);
        $transactionLog = $transactionProcessor->findTransactionById($transaction->getId());

        if ($transactionLog && $transactionLog->getisProcessed()) {
            return new JsonResponse($transactionLog->getLatestResponse(), Response::HTTP_OK, [], true);
        } elseif (!$transactionLog) {
            $transactionLog = $transactionProcessor->addSingleLog($transaction);
        }

        try {
            $data = $ticketProcessor->getTicketData($transaction);
            $this->getDoctrine()->getManager()->persist(
                $transactionLog
                    ->setIsProcessed(true)
                    ->setLatestResponse(json_encode($data))
            );

            $this->getDoctrine()->getManager()->flush();
        } catch (\Exception $ex) {
            $currentAttempt = $transactionLog->getErrorAttempts() + 1;

            try {
                $emailNotifier->notifyAboutError(
                    $ex->getMessage(),
                    $transactionLog->getTransactionJson(),
                    'GoEuro error notification'
                );
            } catch (\Exception $emailNotifierException) {
                // continue
            }

            $this->getDoctrine()->getManager()->persist(
                $transactionLog
                    ->setLatestResponse($ex->getMessage())
                    ->setErrorAttempts($currentAttempt)
            );

            $this->getDoctrine()->getManager()->flush();

            throw new ApiException((new ApiProblem(ApiProblem::TYPE_TICKET_CREATION_ERROR))->set('errorMessage', $ex->getMessage()));
        }

        return new JsonResponse($data, Response::HTTP_CREATED);
    }
}
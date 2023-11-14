<?php

namespace App\Service\Autostradale;

use App\Mapping\Autostradale\Booking;
use App\Entity\AutostradaleBookingLog;
use App\Repository\AutostradaleBookingLogRepository;
use Doctrine\ORM\EntityManagerInterface;

class LoggerProcessor
{
    /**
     * @var AutostradaleBookingLog
     */
    private $bookingLog;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var AutostradaleBookingLogRepository
     */
    private $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository(AutostradaleBookingLog::class);
        $this->bookingLog = new AutostradaleBookingLog();
        $this->bookingLog->setCreatedAt(new \DateTime());
    }

    public function addRequestData(array $requestData, $orderItemId)
    {
        if ($orderItemId) {
            $this->bookingLog->setOrderItemId($orderItemId);
        }

        $this->bookingLog->setRequestData(json_encode($requestData, JSON_PRETTY_PRINT));
        $this->saveLog();
    }

    public function addResponseData(array $responseData)
    {
        $this->bookingLog->setLastResponse(json_encode($responseData, JSON_PRETTY_PRINT));
        $this->bookingLog->setBookingId($responseData['IdBooking'] ?? null);
        $this->bookingLog->setOriginalTicketUrl($responseData['LinkTicketPdf'] ?? null);
        $this->saveLog();
    }

    public function addModifiedTicketLink(string $modifiedTicketLink)
    {
        $this->bookingLog->setModifiedTicketUrl($modifiedTicketLink);
        $this->saveLog();
    }

    public function objectToArray($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
        }
        if (is_array($obj)) {
            $new = [];
            foreach ($obj as $key => $val) {
                $new[$key] = $this->objectToArray($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    public function findTicketUrl($orderItemId)
    {
        $bookingLog = $this->entityManager
            ->getRepository(AutostradaleBookingLog::class)
            ->findOneBy(['orderItemId' => $orderItemId]);
        if ($bookingLog) {
            return $bookingLog->getModifiedTicketUrl();
        } else {
            return null;
        }
    }

    public function getOrderItemLatestBookingIdTickets($originalBookingId)
    {
        /** @var AutostradaleBookingLog $originalBookingLog */
        $originalBookingLog = $this->repository->findOneBy(['bookingId' => $originalBookingId]);
        $orderItemId = $originalBookingLog->getOrderItemId();
        if (!$orderItemId) {
            throw new \Exception('Order item id is missing in the booking');
        }

        /** @var AutostradaleBookingLog $latestBookingLog */
        $latestBookingLog = $this->repository->findOneBy(['orderItemId' => $orderItemId], ['originalTicketUrl' => 'DESC']);
        $latestBookingId = $latestBookingLog->getBookingId();
        $latestBookingTickets = json_decode($latestBookingLog->getLastResponse(), true)['Tickets'];

        return [$orderItemId, $latestBookingId, $latestBookingTickets];
    }

    public function getLogByOrderItemId($orderItemId)
    {
        if (!$orderItemId) {
            return null;
        }

        $query = $this->repository->createQueryBuilder('l')
            ->where('l.orderItemId = :orderItemId')
            ->andWhere('l.originalTicketUrl IS NOT NULL')
            ->orderBy('l.id', 'DESC')
            ->setParameter('orderItemId', $orderItemId)
            ->setMaxResults(1)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    private function saveLog()
    {
        $this->entityManager->persist($this->bookingLog);
        $this->entityManager->flush();
    }
}
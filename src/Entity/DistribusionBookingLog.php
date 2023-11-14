<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DistribusionBookingLogRepository")
 * @ORM\Table(name="distribusion_booking_log")
 */
class DistribusionBookingLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $orderId;

    /**
     * @ORM\Column(type="string")
     */
    private $bookingId;

    /**
     * @ORM\Column(type="string")
     */
    private $ticketUrl;

    /**
     * @ORM\Column(type="json_array")
     */
    private $request;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $lastResponse;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $orderItemId;

    /**
     * @ORM\Column(type="integer")
     */
    private $status = 0;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return DistribusionBookingLog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param mixed $orderId
     * @return DistribusionBookingLog
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBookingId()
    {
        return $this->bookingId;
    }

    /**
     * @param mixed $bookingId
     * @return DistribusionBookingLog
     */
    public function setBookingId($bookingId)
    {
        $this->bookingId = $bookingId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTicketUrl()
    {
        return $this->ticketUrl;
    }

    /**
     * @param mixed $ticketUrl
     * @return DistribusionBookingLog
     */
    public function setTicketUrl($ticketUrl)
    {
        $this->ticketUrl = $ticketUrl;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $request
     * @return DistribusionBookingLog
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * @param mixed $lastResponse
     * @return DistribusionBookingLog
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     * @return DistribusionBookingLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    /**
     * @param mixed $orderItemId
     * @return DistribusionBookingLog
     */
    public function setOrderItemId($orderItemId)
    {
        $this->orderItemId = $orderItemId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return DistribusionBookingLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }
}
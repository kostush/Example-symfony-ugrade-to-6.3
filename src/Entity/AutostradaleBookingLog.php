<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AutostradaleBookingLogRepository")
 * @ORM\Table(name="autostradale_booking_log")
 */
class AutostradaleBookingLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $bookingId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $originalTicketUrl;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $modifiedTicketUrl;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $requestData;

    /**
     * @ORM\Column(type="text", nullable=true)
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return AutostradaleBookingLog
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return AutostradaleBookingLog
     */
    public function setBookingId($bookingId)
    {
        $this->bookingId = $bookingId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalTicketUrl()
    {
        return $this->originalTicketUrl;
    }

    /**
     * @param mixed $originalTicketUrl
     * @return AutostradaleBookingLog
     */
    public function setOriginalTicketUrl($originalTicketUrl)
    {
        $this->originalTicketUrl = $originalTicketUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModifiedTicketUrl()
    {
        return $this->modifiedTicketUrl;
    }

    /**
     * @param mixed $modifiedTicketUrl
     * @return AutostradaleBookingLog
     */
    public function setModifiedTicketUrl($modifiedTicketUrl)
    {
        $this->modifiedTicketUrl = $modifiedTicketUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @param mixed $requestData
     * @return AutostradaleBookingLog
     */
    public function setRequestData($requestData)
    {
        $this->requestData = $requestData;
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
     * @return AutostradaleBookingLog
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
     * @return AutostradaleBookingLog
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
     * @return AutostradaleBookingLog
     */
    public function setOrderItemId($orderItemId)
    {
        $this->orderItemId = $orderItemId;
        return $this;
    }
}
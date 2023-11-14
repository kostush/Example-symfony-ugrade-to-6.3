<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WemovoTransactionLogRepository")
 * @ORM\Table(name="wemovo_transaction_log")
 */
class WemovoTransactionLog
{
    const MAX_ERROR_ATTEMPTS = 5;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="bigint", nullable=true)
     */
    private $transactionId;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $transactionJson;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $travelDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $latestResponse;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $errorAttempts = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isProcessed = false;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return WemovoTransactionLog
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param mixed $transactionId
     * @return WemovoTransactionLog
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTransactionJson()
    {
        return $this->transactionJson;
    }

    /**
     * @param mixed $transactionJson
     * @return WemovoTransactionLog
     */
    public function setTransactionJson($transactionJson)
    {
        $this->transactionJson = $transactionJson;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTravelDate()
    {
        return $this->travelDate;
    }

    /**
     * @param mixed $travelDate
     * @return WemovoTransactionLog
     */
    public function setTravelDate($travelDate)
    {
        $this->travelDate = $travelDate;

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
     * @return WemovoTransactionLog
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLatestResponse()
    {
        return $this->latestResponse;
    }

    /**
     * @param mixed $latestResponse
     * @return WemovoTransactionLog
     */
    public function setLatestResponse($latestResponse)
    {
        $this->latestResponse = $latestResponse;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrorAttempts()
    {
        return $this->errorAttempts;
    }

    /**
     * @param mixed $errorAttempts
     * @return WemovoTransactionLog
     */
    public function setErrorAttempts($errorAttempts)
    {
        $this->errorAttempts = $errorAttempts;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisProcessed()
    {
        return $this->isProcessed;
    }

    /**
     * @param mixed $isProcessed
     * @return WemovoTransactionLog
     */
    public function setIsProcessed($isProcessed)
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }
}
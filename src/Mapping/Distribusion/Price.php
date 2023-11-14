<?php

namespace App\Mapping\Distribusion;

use Symfony\Component\Validator\Constraints as Assert;

class Price implements DistribusionMapping
{
    /**
     * @var int
     */
    private $departureStationId;

    /** @var int */
    private $arrivalStationId;

    /** @var string */
    private $convertedDepartureStationId;

    /** @var string */
    private $convertedArrivalStationId;

    /** @var string */
    private $departureTime;

    /**
     * @Assert\Choice(
     *     choices = {"EUR", "GBP"},
     *     message = "Invalid currency"
     * )
     */
    private $currency;

    /**
     * @var int
     */
    private $adults;

    /**
     * @var int
     */
    private $children;

    /**
     * @var int
     */
    private $infants;

    /**
     * @var string
     */
    private $sessionUniqueId;

    /** @var array */
    private $extraData = [];

    /**
     * @return int
     */
    public function getDepartureStationId(): int
    {
        return $this->departureStationId;
    }

    /**
     * @param int $departureStationId
     * @return Price
     */
    public function setDepartureStationId(int $departureStationId): DistribusionMapping
    {
        $this->departureStationId = $departureStationId;
        return $this;
    }

    /**
     * @return int
     */
    public function getArrivalStationId(): int
    {
        return $this->arrivalStationId;
    }

    /**
     * @param int $arrivalStationId
     * @return Price
     */
    public function setArrivalStationId(int $arrivalStationId): DistribusionMapping
    {
        $this->arrivalStationId = $arrivalStationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getConvertedDepartureStationId(): string
    {
        return $this->convertedDepartureStationId;
    }

    /**
     * @param string $convertedDepartureStationId
     * @return Price
     */
    public function setConvertedDepartureStationId(string $convertedDepartureStationId): DistribusionMapping
    {
        $this->convertedDepartureStationId = $convertedDepartureStationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getConvertedArrivalStationId(): string
    {
        return $this->convertedArrivalStationId;
    }

    /**
     * @param string $convertedArrivalStationId
     * @return Price
     */
    public function setConvertedArrivalStationId(string $convertedArrivalStationId): DistribusionMapping
    {
        $this->convertedArrivalStationId = $convertedArrivalStationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureTime(): string
    {
        return $this->departureTime;
    }

    /**
     * @param string $date
     * @return Price
     */
    public function setDepartureTime(string $departureTime): Price
    {
        $this->departureTime = $departureTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getAdults(): string
    {
        return $this->adults;
    }

    /**
     * @param int $adults
     * @return Price
     */
    public function setAdults(int $adults): Price
    {
        $this->adults = $adults;
        return $this;
    }

    /**
     * @return int
     */
    public function getChildren(): string
    {
        return $this->children;
    }

    /**
     * @param int $children
     * @return Price
     */
    public function setChildren(int $children): Price
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @return int
     */
    public function getInfants(): string
    {
        return $this->infants;
    }

    /**
     * @param int $infants
     * @return Price
     */
    public function setInfants(int $infants): Price
    {
        $this->infants = $infants;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionUniqueId(): ?string
    {
        return  $this->sessionUniqueId;
    }

    /**
     * @param string|null $sessionUniqueId
     * @return Price
     */
    public function setSessionUniqueId(?string $sessionUniqueId): Price
    {
        $this->sessionUniqueId = $sessionUniqueId;

        return $this;
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array $extraData
     * @return Price
     */
    public function setExtraData(array $extraData): Price
    {
        $this->extraData = $extraData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     * @return Price
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }
}
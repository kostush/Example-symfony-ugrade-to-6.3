<?php

namespace App\Mapping\Distribusion;


class Schedule implements DistribusionMapping
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
    private $date;

    /**
     * @return int
     */
    public function getDepartureStationId(): int
    {
        return $this->departureStationId;
    }

    /**
     * @param int $departureStationId
     * @return DistribusionMapping
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
     * @return DistribusionMapping
     */
    public function setArrivalStationId(int $arrivalStationId): DistribusionMapping
    {
        $this->arrivalStationId = $arrivalStationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getConvertedDepartureStationId(): ?string
    {
        return $this->convertedDepartureStationId;
    }

    /**
     * @param string $convertedDepartureStationId
     * @return DistribusionMapping
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
     * @return Schedule
     */
    public function setConvertedArrivalStationId(string $convertedArrivalStationId): DistribusionMapping
    {
        $this->convertedArrivalStationId = $convertedArrivalStationId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     * @return Schedule
     */
    public function setDate(string $date): Schedule
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getSessionUniqueId(): ?string
    {
        // TODO: Implement getSessionId() method.
    }
}
<?php

namespace App\Mapping\Distribusion;

interface DistribusionMapping
{
    /**
     * @param $convertedDepartureStationId
     * @return DistribusionMapping
     */
    public function setConvertedDepartureStationId(string $convertedDepartureStationId): DistribusionMapping;

    /**
     * @param $convertedArrivalStationId
     * @return DistribusionMapping
     */
    public function setConvertedArrivalStationId(string $convertedArrivalStationId): DistribusionMapping;

    /**
     * @return int
     */
    public function getDepartureStationId(): int;

    /**
     * @return int
     */
    public function getArrivalStationId(): int;

    /**
     * @param int $departureStationId
     * @return DistribusionMapping
     */
    public function setDepartureStationId(int $departureStationId): DistribusionMapping;

    /**
     * @param int $arrivalStationId
     * @return DistribusionMapping
     */
    public function setArrivalStationId(int $arrivalStationId): DistribusionMapping;

    /**
     * @return string
     */
    public function getSessionUniqueId(): ?string;
}
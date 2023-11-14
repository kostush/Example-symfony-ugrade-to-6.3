<?php

namespace App\Mapping\Distribusion;

use Symfony\Component\Validator\Constraints as Assert;

class Booking implements DistribusionMapping
{
    /** @var string */
    private $orderItemId;

    /** @var int */
    private $departureStationId;

    /** @var int */
    private $arrivalStationId;

    /** @var string */
    private $convertedDepartureStationId;

    /** @var string */
    private $convertedArrivalStationId;

    /** @var string */
    private $departureTime;

    /** @var string */
    private $price;

    /** @var Customer */
    private $customer;

    /** @var array */
    private $passengers;

    /** @var array */
    private $extraData = [];

    /** @var string|null */
    private $sessionUniqueId;

    /**
     * @Assert\Choice(
     *     choices = {"EUR", "GBP"},
     *     message = "Invalid currency"
     * )
     */
    private $currency;

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Booking
     */
    public function setCurrency(string $currency): Booking
    {
        $this->currency = $currency;

        return $this;
    }


    /**
     * @return string|null
     */
    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    /**
     * @param string $orderItemId
     * @return Booking
     */
    public function setOrderItemId($orderItemId): Booking
    {
        $this->orderItemId = $orderItemId;

        return $this;
    }

    /**
     * @return int
     */
    public function getDepartureStationId(): int
    {
        return $this->departureStationId;
    }

    /**
     * @param int $departureStationId
     * @return Booking
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
     * @return Booking
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
     * @return Booking
     */
    public function setConvertedDepartureStationId(string $convertedDepartureStationId): DistribusionMapping
    {
        $this->convertedDepartureStationId = $convertedDepartureStationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getConvertedArrivalStationId(): ?string
    {
        return $this->convertedArrivalStationId;
    }

    /**
     * @param string $convertedArrivalStationId
     * @return Booking
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
     * @param string $departureTime
     * @return Booking
     */
    public function setDepartureTime($departureTime): Booking
    {
        $this->departureTime = $departureTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $price
     * @return Booking
     */
    public function setPrice($price): Booking
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return Booking
     */
    public function setCustomer(Customer $customer): Booking
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Passenger[]
     */
    public function getPassengers(): ?array
    {
        return $this->passengers;
    }

    /**
     * @param Passenger[] $passengers
     * @return Booking
     */
    public function setPassengers(?array $passengers): Booking
    {
        $this->passengers = $passengers;

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
     * @return Booking
     */
    public function setExtraData(array $extraData): Booking
    {
        $this->extraData = $extraData;

        return $this;
    }

    /**
     * @return string
     */
    public function getSessionUniqueId(): ?string
    {
        return $this->sessionUniqueId;
    }

    /**
     * @param string|null $sessionUniqueId
     * @return Booking
     */
    public function setSessionUniqueId(?string $sessionUniqueId): Booking
    {
        $this->sessionUniqueId = $sessionUniqueId;

        return $this;
    }
}
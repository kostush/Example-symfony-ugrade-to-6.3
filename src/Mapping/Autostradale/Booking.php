<?php

namespace App\Mapping\Autostradale;

class Booking
{
    /** @var string */
    private $orderItemId;

    /** @var string */
    private $departureCityId;

    /** @var string */
    private $departureStationId;

    /** @var string */
    private $departureStationName;

    /** @var string */
    private $arrivalCityId;

    /** @var string */
    private $arrivalStationId;

    /** @var string */
    private $arrivalStationName;

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

    public function __construct()
    {
        $this->customer = new Customer();
    }

    /**
     * @return string
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
     * @return string
     */
    public function getDepartureCityId()
    {
        return $this->departureCityId;
    }

    /**
     * @param string $departureCityId
     * @return Booking
     */
    public function setDepartureCityId($departureCityId): Booking
    {
        $this->departureCityId = $departureCityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureStationId()
    {
        return $this->departureStationId;
    }

    /**
     * @param string $departureStationId
     * @return Booking
     */
    public function setDepartureStationId($departureStationId): Booking
    {
        $this->departureStationId = $departureStationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureStationName()
    {
        return $this->departureStationName;
    }

    /**
     * @param string $departureStationName
     * @return Booking
     */
    public function setDepartureStationName($departureStationName): Booking
    {
        $this->departureStationName = $departureStationName;

        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalCityId()
    {
        return $this->arrivalCityId;
    }

    /**
     * @param string $arrivalCityId
     * @return Booking
     */
    public function setArrivalCityId($arrivalCityId): Booking
    {
        $this->arrivalCityId = $arrivalCityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalStationId()
    {
        return $this->arrivalStationId;
    }

    /**
     * @param string $arrivalStationId
     * @return Booking
     */
    public function setArrivalStationId($arrivalStationId): Booking
    {
        $this->arrivalStationId = $arrivalStationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalStationName()
    {
        return $this->arrivalStationName;
    }

    /**
     * @param string $arrivalStationName
     * @return Booking
     */
    public function setArrivalStationName($arrivalStationName): Booking
    {
        $this->arrivalStationName = $arrivalStationName;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureTime()
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
     * @return array
     */
    public function getPassengers(): ?array
    {
        return $this->passengers;
    }

    /**
     * @param array $passengers
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
}
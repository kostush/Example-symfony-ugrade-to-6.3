<?php

namespace App\Mapping\Autostradale;

class BookingUpdate
{
    /** @var string */
    private $bookingId;

    /** @var string */
    private $vendorId;

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
    private $departureTimeNew;

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
    public function getBookingId()
    {
        return $this->bookingId;
    }

    /**
     * @param string $bookingId
     * @return BookingUpdate
     */
    public function setBookingId($bookingId): BookingUpdate
    {
        $this->bookingId = $bookingId;

        return $this;
    }

    /**
     * @return string
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @param string $vendorId
     * @return BookingUpdate
     */
    public function setVendorId($vendorId): BookingUpdate
    {
        $this->vendorId = $vendorId;

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
     * @return BookingUpdate
     */
    public function setDepartureCityId($departureCityId): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setDepartureStationId($departureStationId): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setDepartureStationName($departureStationName): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setArrivalCityId($arrivalCityId): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setArrivalStationId($arrivalStationId): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setArrivalStationName($arrivalStationName): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setDepartureTime($departureTime): BookingUpdate
    {
        $this->departureTime = $departureTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureTimeNew()
    {
        return $this->departureTimeNew;
    }

    /**
     * @param string $departureTimeNew
     * @return BookingUpdate
     */
    public function setDepartureTimeNew($departureTimeNew): BookingUpdate
    {
        $this->departureTimeNew = $departureTimeNew;

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
     * @return BookingUpdate
     */
    public function setPrice($price): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setCustomer(Customer $customer): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setPassengers(?array $passengers): BookingUpdate
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
     * @return BookingUpdate
     */
    public function setExtraData(array $extraData): BookingUpdate
    {
        $this->extraData = $extraData;

        return $this;
    }
}
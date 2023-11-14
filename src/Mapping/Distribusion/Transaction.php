<?php

namespace App\Mapping\Distribusion;

class Transaction implements \JsonSerializable
{
    /** @var string */
    private $customerMail;

    /** @var string */
    private $customerTelephone;

    /** @var string */
    private $customerLanguage;

    /** @var string */
    private $customerCountry;

    /** @var string */
    private $customerName;

    /** @var string */
    private $ticketNr;

    /** @var string */
    private $bookingTime;

    /** @var string */
    private $departureStop;

    /** @var string */
    private $departureStopName;

    /** @var string */
    private $arrivalStop;

    /** @var string */
    private $arrivalStopName;

    /** @var string */
    private $departureDatetime;

    /** @var string */
    private $arrivalDatetime;

    /** @var double */
    private $price;

    /** @var double */
    private $commission;

    /** @var string */
    private $currency;

    /** @var string */
    private $agency;

    /** @var integer */
    private $totalPassengers;

    /**
     * @return string
     */
    public function getCustomerMail(): ?string
    {
        return $this->customerMail;
    }

    /**
     * @param string $customerMail
     * @return Transaction
     */
    public function setCustomerMail(?string $customerMail): Transaction
    {
        $this->customerMail = $customerMail;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerTelephone(): ?string
    {
        return $this->customerTelephone;
    }

    /**
     * @param string $customerTelephone
     * @return Transaction
     */
    public function setCustomerTelephone(?string $customerTelephone): Transaction
    {
        $this->customerTelephone = $customerTelephone;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerLanguage(): ?string
    {
        return $this->customerLanguage;
    }

    /**
     * @param string $customerLanguage
     * @return Transaction
     */
    public function setCustomerLanguage(?string $customerLanguage): Transaction
    {
        $this->customerLanguage = $customerLanguage;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerCountry(): ?string
    {
        return $this->customerCountry;
    }

    /**
     * @param string $customerCountry
     * @return Transaction
     */
    public function setCustomerCountry(?string $customerCountry): Transaction
    {
        $this->customerCountry = $customerCountry;

        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return Transaction
     */
    public function setCustomerName(?string $customerName): Transaction
    {
        $this->customerName = $customerName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicketNr(): ?string
    {
        return $this->ticketNr;
    }

    /**
     * @param string $ticketNr
     * @return Transaction
     */
    public function setTicketNr(?string $ticketNr): Transaction
    {
        $this->ticketNr = $ticketNr;

        return $this;
    }

    /**
     * @return string
     */
    public function getBookingTime(): ?string
    {
        return $this->bookingTime;
    }

    /**
     * @param string $bookingTime
     * @return Transaction
     */
    public function setBookingTime(?string $bookingTime): Transaction
    {
        $this->bookingTime = $bookingTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureStop(): ?string
    {
        return $this->departureStop;
    }

    /**
     * @param string $departureStop
     * @return Transaction
     */
    public function setDepartureStop(?string $departureStop): Transaction
    {
        $this->departureStop = $departureStop;

        return $this;
    }

    /**
     * @return string
     */
    public function getDepartureStopName(): ?string
    {
        return $this->departureStopName;
    }

    /**
     * @param string $departureStopName
     * @return Transaction
     */
    public function setDepartureStopName(?string $departureStopName): Transaction
    {
        $this->departureStopName = $departureStopName;

        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalStop(): ?string
    {
        return $this->arrivalStop;
    }

    /**
     * @param string $arrivalStop
     * @return Transaction
     */
    public function setArrivalStop(?string $arrivalStop): Transaction
    {
        $this->arrivalStop = $arrivalStop;

        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalStopName(): ?string
    {
        return $this->arrivalStopName;
    }

    /**
     * @param string $arrivalStopName
     * @return Transaction
     */
    public function setArrivalStopName(?string $arrivalStopName): Transaction
    {
        $this->arrivalStopName = $arrivalStopName;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDepartureDatetime(): ?\DateTime
    {
        return \DateTime::createFromFormat('Y/m/d H:i:s', $this->departureDatetime);
    }

    /**
     * @param string $departureDatetime
     * @return Transaction
     */
    public function setDepartureDatetime(?string $departureDatetime): Transaction
    {
        $this->departureDatetime = $departureDatetime;

        return $this;
    }

    /**
     * @return string
     */
    public function getArrivalDatetime(): ?string
    {
        return $this->arrivalDatetime;
    }

    /**
     * @param string $arrivalDatetime
     * @return Transaction
     */
    public function setArrivalDatetime(?string $arrivalDatetime): Transaction
    {
        $this->arrivalDatetime = $arrivalDatetime;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return Transaction
     */
    public function setPrice(?float $price): Transaction
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getCommission(): ?float
    {
        return $this->commission;
    }

    /**
     * @param float $commission
     * @return Transaction
     */
    public function setCommission(?float $commission): Transaction
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return Transaction
     */
    public function setCurrency(?string $currency): Transaction
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string
     */
    public function getAgency(): ?string
    {
        return $this->agency;
    }

    /**
     * @param string $agency
     * @return Transaction
     */
    public function setAgency(?string $agency): Transaction
    {
        $this->agency = $agency;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalPassengers(): ?int
    {
        return $this->totalPassengers;
    }

    /**
     * @param int $totalPassengers
     * @return Transaction
     */
    public function setTotalPassengers(?int $totalPassengers): Transaction
    {
        $this->totalPassengers = $totalPassengers;

        return $this;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
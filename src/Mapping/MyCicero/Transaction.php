<?php

namespace App\Mapping\MyCicero;

class Transaction
{
    /**
     * @var integer
     */
    private $bookingId;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var Price
     */
    private $price;

    /**
     * @var string
     */
    private $type;

    /**
     * @var PassengerName
     */
    private $passengerName;

    /**
     * @var PassengerTypes
     */
    private $passengerTypes;

    /**
     * @var \DateTime
     */
    private $travelDate;

    /**
     * @var string
     */
    private $ticketCode;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var string
     */
    private $originCode;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $destinationCode;

    /**
     * @return int
     */
    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    /**
     * @param int $bookingId
     * @return Transaction
     */
    public function setBookingId(?int $bookingId): Transaction
    {
        $this->bookingId = $bookingId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Transaction
     */
    public function setCreatedAt(?\DateTime $createdAt): ?Transaction
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Price
     */
    public function getPrice(): ?Price
    {
        return $this->price;
    }

    /**
     * @param Price $price
     * @return Transaction
     */
    public function setPrice(?Price $price): ?Transaction
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Transaction
     */
    public function setType(?string $type): ?Transaction
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return PassengerName
     */
    public function getPassengerName(): ?PassengerName
    {
        return $this->passengerName;
    }

    /**
     * @param PassengerName $passengerName
     * @return Transaction
     */
    public function setPassengerName(?PassengerName $passengerName): ?Transaction
    {
        $this->passengerName = $passengerName;

        return $this;
    }

    /**
     * @return PassengerTypes
     */
    public function getPassengerTypes(): PassengerTypes
    {
        return $this->passengerTypes;
    }

    /**
     * @param PassengerTypes $passengerTypes
     * @return Transaction
     */
    public function setPassengerTypes(PassengerTypes $passengerTypes): Transaction
    {
        $this->passengerTypes = $passengerTypes;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTravelDate(): ?\DateTime
    {
        return $this->travelDate;
    }

    /**
     * @param \DateTime $travelDate
     * @return Transaction
     */
    public function setTravelDate(?\DateTime $travelDate): ?Transaction
    {
        $this->travelDate = $travelDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicketCode(): ?string
    {
        return $this->ticketCode;
    }

    /**
     * @param string $ticketCode
     * @return Transaction
     */
    public function setTicketCode(?string $ticketCode): ?Transaction
    {
        $this->ticketCode = $ticketCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     * @return Transaction
     */
    public function setOrigin(?string $origin): ?Transaction
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginCode()
    {
        return $this->originCode;
    }

    /**
     * @param string $originCode
     * @return Transaction
     */
    public function setOriginCode($originCode): ?Transaction
    {
        $this->originCode = $originCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestination(): ?string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     * @return Transaction
     */
    public function setDestination(?string $destination): ?Transaction
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationCode()
    {
        return $this->destinationCode;
    }

    /**
     * @param string $destinationCode
     * @return Transaction
     */
    public function setDestinationCode($destinationCode): ?Transaction
    {
        $this->destinationCode = $destinationCode;

        return $this;
    }
}
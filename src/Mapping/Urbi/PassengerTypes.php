<?php

namespace App\Mapping\Urbi;

class PassengerTypes
{
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
     * @return int
     */
    public function getAdults(): int
    {
        return $this->adults;
    }

    /**
     * @param int $adults
     * @return PassengerTypes
     */
    public function setAdults(int $adults): PassengerTypes
    {
        $this->adults = $adults;

        return $this;
    }

    /**
     * @return int
     */
    public function getChildren(): int
    {
        return $this->children;
    }

    /**
     * @param int $children
     * @return PassengerTypes
     */
    public function setChildren(int $children): PassengerTypes
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return int
     */
    public function getInfants(): int
    {
        return $this->infants;
    }

    /**
     * @param int $infants
     * @return PassengerTypes
     */
    public function setInfants(int $infants): PassengerTypes
    {
        $this->infants = $infants;

        return $this;
    }
}
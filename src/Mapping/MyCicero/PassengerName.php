<?php

namespace App\Mapping\MyCicero;

class PassengerName
{
    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return PassengerName
     */
    public function setFirstName(?string $firstName): ?PassengerName
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return PassengerName
     */
    public function setLastName(?string $lastName): ?PassengerName
    {
        $this->lastName = $lastName;
        
        return $this;
    }
}
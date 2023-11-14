<?php

namespace App\Mapping\Distribusion;

class Customer
{
    /** @var string */
    private $gender;

    /** @var string */
    private $firstName;

    /** @var string */
    private $lastName;

    /** @var string */
    private $email;

    /** @var string */
    private $phone;

    /** @var string */
    private $city = 'Rome';

    /** @var string */
    private $zipCode = '00197';

    /** @var string */
    private $streetAndNumber = 'Via Archimede, 164';

    /**
     * @return string
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     * @return Customer
     */
    public function setGender(?string $gender): Customer
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return Customer
     */
    public function setFirstName(?string $firstName): Customer
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
     * @return Customer
     */
    public function setLastName(?string $lastName): Customer
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Customer
     */
    public function setEmail(?string $email): Customer
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return Customer
     */
    public function setPhone(?string $phone): Customer
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return Customer
     */
    public function setCity(?string $city): Customer
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     * @return Customer
     */
    public function setZipCode(?string $zipCode): Customer
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreetAndNumber(): ?string
    {
        return $this->streetAndNumber;
    }

    /**
     * @param string $streetAndNumber
     * @return Customer
     */
    public function setStreetAndNumber(?string $streetAndNumber): Customer
    {
        $this->streetAndNumber = $streetAndNumber;

        return $this;
    }
}
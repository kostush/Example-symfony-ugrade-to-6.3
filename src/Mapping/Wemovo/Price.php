<?php

namespace App\Mapping\Wemovo;

class Price
{
    /**
     * @var integer
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @return int
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return Price
     */
    public function setAmount(?int $amount): ?Price
    {
        $this->amount = $amount;

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
     * @return Price
     */
    public function setCurrency(?string $currency): ?Price
    {
        $this->currency = $currency;

        return $this;
    }
}
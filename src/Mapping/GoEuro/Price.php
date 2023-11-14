<?php

namespace App\Mapping\GoEuro;

class Price
{
    /**
     * @var integer
     */
    private $lowestUnitValue;

    /**
     * @var string
     */
    private $currency;

    /**
     * @return int
     */
    public function getLowestUnitValue(): ?int
    {
        return $this->lowestUnitValue;
    }

    /**
     * @param int $lowestUnitValue
     * @return Price
     */
    public function setLowestUnitValue(?int $lowestUnitValue): ?Price
    {
        $this->lowestUnitValue = $lowestUnitValue;

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
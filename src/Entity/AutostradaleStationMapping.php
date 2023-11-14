<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AutostradaleStationMappingRepository")
 * @ORM\Table(name="autostradale_station_mapping")
 */
class AutostradaleStationMapping
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $cityCode;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $stationCode;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $stationName;

    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $internalCode;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return AutostradaleStationMapping
     */
    public function setId($id): AutostradaleStationMapping
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    /**
     * @param string $cityCode
     * @return AutostradaleStationMapping
     */
    public function setCityCode(?string $cityCode): AutostradaleStationMapping
    {
        $this->cityCode = $cityCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getStationCode(): ?string
    {
        return $this->stationCode;
    }

    /**
     * @param string $stationCode
     * @return AutostradaleStationMapping
     */
    public function setStationCode(?string $stationCode): AutostradaleStationMapping
    {
        $this->stationCode = $stationCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getStationName(): string
    {
        return $this->stationName;
    }

    /**
     * @param string $stationName
     * @return AutostradaleStationMapping
     */
    public function setStationName(string $stationName): AutostradaleStationMapping
    {
        $this->stationName = $stationName;

        return $this;
    }

    /**
     * @return int
     */
    public function getInternalCode(): int
    {
        return $this->internalCode;
    }

    /**
     * @param int $internalCode
     * @return AutostradaleStationMapping
     */
    public function setInternalCode(int $internalCode): AutostradaleStationMapping
    {
        $this->internalCode = $internalCode;

        return $this;
    }


}
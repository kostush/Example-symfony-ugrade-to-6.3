<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GoEuroStationMappingRepository")
 * @ORM\Table(name="go_euro_station_mapping")
 */
class GoEuroStationMapping
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", unique=true)
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
     * @return GoEuroStationMapping
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getStationCode(): string
    {
        return $this->stationCode;
    }

    /**
     * @param string $stationCode
     * @return GoEuroStationMapping
     */
    public function setStationCode(string $stationCode): GoEuroStationMapping
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
     * @return GoEuroStationMapping
     */
    public function setStationName(string $stationName): GoEuroStationMapping
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
     * @return GoEuroStationMapping
     */
    public function setInternalCode(int $internalCode): GoEuroStationMapping
    {
        $this->internalCode = $internalCode;

        return $this;
    }


}
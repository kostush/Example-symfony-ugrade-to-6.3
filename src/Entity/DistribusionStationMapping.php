<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DistribusionStationMappingRepository")
 * @ORM\Table(name="distribusion_station_mapping")
 */
class DistribusionStationMapping
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
    private $stationId;

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
     * @return DistribusionStationMapping
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getStationId(): ?string
    {
        return $this->stationId;
    }

    /**
     * @param string $stationId
     * @return DistribusionStationMapping
     */
    public function setStationId(?string $stationId): DistribusionStationMapping
    {
        $this->stationId = $stationId;

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
     * @return DistribusionStationMapping
     */
    public function setStationCode(?string $stationCode): DistribusionStationMapping
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
     * @return DistribusionStationMapping
     */
    public function setStationName(string $stationName): DistribusionStationMapping
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
     * @return DistribusionStationMapping
     */
    public function setInternalCode(int $internalCode): DistribusionStationMapping
    {
        $this->internalCode = $internalCode;

        return $this;
    }


}
<?php

namespace App\Service\Autostradale;

use App\Entity\AutostradaleStationMapping;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Repository\AutostradaleStationMappingRepository;
use Doctrine\ORM\EntityManagerInterface;

class RideCodeGenerator
{
    /**
     * @var AutostradaleStationMappingRepository
     */
    private $AutostradaleRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->AutostradaleRepository = $entityManager->getRepository(AutostradaleStationMapping::class);
    }

    public function getStationMapping($stationCode): AutostradaleStationMapping
    {
        /** @var AutostradaleStationMapping $mapping */
        $mapping = $this->AutostradaleRepository->findOneBy(['internalCode' => $stationCode]);

        if (!$mapping) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_STATION_NOT_FOUND))->set('station', $stationCode));
        }

        return $mapping;
    }
}
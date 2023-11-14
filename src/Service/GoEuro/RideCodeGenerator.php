<?php

namespace App\Service\GoEuro;

use App\Entity\GoEuroStationMapping;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Repository\GoEuroStationMappingRepository;
use Doctrine\ORM\EntityManagerInterface;

class RideCodeGenerator
{
    /**
     * @var GoEuroStationMappingRepository
     */
    private $goEuroRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->goEuroRepository = $entityManager->getRepository(GoEuroStationMapping::class);
    }

    public function convertGoEuroCode(?string $stationCode)
    {
        $stationCode = strtoupper($stationCode);
        /** @var GoEuroStationMapping $mapping */
        $mapping = $this->goEuroRepository->findOneBy(['stationCode' => $stationCode]);

        if (!$mapping) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_STATION_NOT_FOUND))->set('station', $stationCode));
        }

        return $mapping->getInternalCode();
    }
}
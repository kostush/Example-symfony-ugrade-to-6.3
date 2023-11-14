<?php

namespace App\Service\Distribusion;

use App\Entity\DistribusionStationMapping;
use App\Exception\ApiException;
use App\Exception\ApiProblem;
use App\Mapping\Distribusion\Transaction;
use App\Repository\DistribusionStationMappingRepository;
use Doctrine\ORM\EntityManagerInterface;

class RideCodeGenerator
{
    /**
     * @var DistribusionStationMappingRepository
     */
    private $DistribusionRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->DistribusionRepository = $entityManager->getRepository(DistribusionStationMapping::class);
    }

    public function convertDistribusionCode(Transaction $transaction, string $direction)
    {
        if ($direction == 'departure') {
            $stationCode = $transaction->getDepartureStop();
        } else {
            $stationCode = $transaction->getArrivalStop();
        }

        /** @var DistribusionStationMapping $mapping */
        $mapping = $this->DistribusionRepository->findOneBy(['stationCode' => $stationCode]);

        if (!$mapping) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_STATION_NOT_FOUND))->set('station', $stationCode));
        }

        if ($direction == 'departure') {
            $transaction->setDepartureStopName($mapping->getStationName());
        } else {
            $transaction->setArrivalStopName($mapping->getStationName());
        }


        return $mapping->getInternalCode();
    }

    public function convertInternalCode($stationCode)
    {
        /** @var DistribusionStationMapping $mapping */
        $mapping = $this->DistribusionRepository->findOneBy(['internalCode' => $stationCode]);

        if (!$mapping) {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_STATION_NOT_FOUND))->set('station', $stationCode));
        }

        return $mapping->getStationId();
    }

    public function convertRideData($rideDataJson)
    {
        $rideData = json_decode($rideDataJson, true);
        $codes = explode('-', $rideData['code']);
        $newCodes = [];

        foreach ($codes as $code) {
            /** @var DistribusionStationMapping $mapping */
            $preparedCode = substr(
                strtoupper($code),
                0, 6
            );
            $mapping = $this->DistribusionRepository->findOneBy(['stationId' => $preparedCode]);
            $newCodes[] = $mapping->getStationCode();
        }

        $rideData['code'] = implode('-', $newCodes);

        return json_encode($rideData);
    }

    public function getRideIdFromVendorCode(string $vendorCode): int
    {
        $mapping = [
            "13887-20846" =>  15,
            "20846-13887" =>  16,
            "13639-14123" =>  382,
            "14123-13639" =>  383,
            "12863-20744" =>  486,
            "20744-12863" =>  487,
            "13133-20744" =>  490,
            "20744-13133" =>  491
        ];

        if (isset($mapping[$vendorCode])) {
            return $mapping[$vendorCode];
        } else {
            throw new ApiException((new ApiProblem(ApiProblem::TYPE_CONNECTION_NOT_FOUND)));
        }
    }
}
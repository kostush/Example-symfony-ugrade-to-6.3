<?php

namespace App\Service\Autostradale;

use App\Mapping\Autostradale\Booking;
use App\Mapping\Autostradale\BookingUpdate;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonConverter
{
    /**
     * @var RideCodeGenerator
     */
    private $rideCodeGenerator;

    public function __construct(RideCodeGenerator $rideCodeGenerator)
    {
        $this->rideCodeGenerator = $rideCodeGenerator;
    }

    public function getBookingUpdate(string $json): BookingUpdate
    {
        $serializer = new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer(null, null, null, new ReflectionExtractor()),
            ],
            [
                new JsonEncoder()
            ]
        );

        /** @var BookingUpdate $booking */
        $booking = $serializer->deserialize($json, BookingUpdate::class, 'json');
        $this->convertInternalCodes($booking);
        $this->convertPassengers($booking);

        return $booking;
    }

    public function getBookings(string $json): array
    {
        $serializer = new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer(null, null, null, new ReflectionExtractor()),
                new ArrayDenormalizer(),
            ],
            [
                new JsonEncoder()
            ]
        );

        $bookings = $serializer->deserialize($json, Booking::class . '[]', 'json');

        foreach ($bookings as $booking) {
            $this->convertInternalCodes($booking);
            $this->convertPassengers($booking);
        }

        return $bookings;
    }

    /**
     * @param Booking|BookingUpdate $booking
     */
    private function convertInternalCodes($booking)
    {
        $departureStationMapping = $this->rideCodeGenerator->getStationMapping($booking->getDepartureStationId());
        $booking->setDepartureCityId($departureStationMapping->getCityCode());
        $booking->setDepartureStationId($departureStationMapping->getStationCode());
        $booking->setDepartureStationName($departureStationMapping->getStationName());

        $arrivalStationMapping = $this->rideCodeGenerator->getStationMapping($booking->getArrivalStationId());
        $booking->setArrivalCityId($arrivalStationMapping->getCityCode());
        $booking->setArrivalStationId($arrivalStationMapping->getStationCode());
        $booking->setArrivalStationName($arrivalStationMapping->getStationName());
    }

    /**
     * @param Booking|BookingUpdate $booking
     */
    private function convertPassengers($booking)
    {
        $totalAdults = 0;
        $totalChildren = 0;

        foreach ($booking->getPassengers() as $passenger) {
            if ($passenger['type'] === 'Adult') {
                $totalAdults++;
            } elseif ($passenger['type'] === 'Child') {
                $totalChildren++;
            }
        }

        $newPassengers = [];
        if ($totalAdults) {
            $newPassengers[] = [
                'Key' => 'INT',
                'Value' => $totalAdults
            ];
        }
        if ($totalChildren) {
            $newPassengers[] = [
                'Key' => 'RID',
                'Value' => $totalChildren
            ];
        }

        $booking->setPassengers($newPassengers);
        $booking->setExtraData([
            'Qty' => sprintf('INT:%d;RID:%d;O70:0', $totalAdults, $totalChildren)
        ]);
    }
}
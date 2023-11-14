<?php

namespace App\Service\Distribusion;

use App\Mapping\Distribusion\Booking;
use App\Mapping\Distribusion\Schedule;
use App\Mapping\Distribusion\Price;
use App\Mapping\Distribusion\Transaction;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonConverter
{
    /**
     * @param string $json
     * @return Transaction
     */
    public function getTransaction(string $json): ?Transaction
    {
        $serializer = $this->getSerializer();

        return $serializer->deserialize($json, Transaction::class, 'json');
    }

    /**
     * @param string $json
     * @return array
     */
    public function getBookings(string $json): array
    {
        $serializer = $this->getSerializer();

        return $serializer->deserialize($json, Booking::class . '[]', 'json');
    }

    public function getPrice(string $json): ?Price
    {
        $serializer = $this->getSerializer();

        return $serializer->deserialize($json, Price::class , 'json');
    }

    /**
     * @param Transaction $transaction
     * @return array
     */
    public function getTravelInfo(Transaction $transaction): array
    {
        $serializer = new Serializer(
            [
                new ObjectNormalizer()
            ]
        );

        return $serializer->normalize($transaction, null, [
            'attributes' => [
                'customerMail',
                'customerTelephone',
                'customerLanguage',
                'customerCountry',
                'customerName',
                'bookingTime',
                'departureStop',
                'arrivalStop',
                'departureDatetime',
                'arrivalDatetime',
                'price',
                'commission',
                'currency',
                'agency',
                'totalPassengers'
            ]
        ]);
    }

    /**
     * @return Serializer
     */
    private function getSerializer(): Serializer
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

        return $serializer;
    }
}
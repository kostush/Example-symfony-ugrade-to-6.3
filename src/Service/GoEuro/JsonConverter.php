<?php

namespace App\Service\GoEuro;

use App\Mapping\GoEuro\Transaction;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonConverter
{
    public function convertTransaction(string $json): ?Transaction
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

        return $serializer->deserialize($json, Transaction::class, 'json');
    }

    /**
     * @param string $json
     * @return array|Transaction[]
     */
    public function convertTransactionsResponse(string $json): array
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

        return $serializer->deserialize($json, Transaction::class . '[]', 'json');
    }

    public function getTransactionJson(Transaction $transaction): ?string
    {
        $serializer = new Serializer(
            [
                new DateTimeNormalizer(),
                new ObjectNormalizer()
            ]
        );

        return json_encode($serializer->normalize($transaction));
    }
}
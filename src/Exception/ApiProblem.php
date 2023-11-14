<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class ApiProblem
{
    const TYPE_VALIDATION_ERROR = 'validation_error';
    const TYPE_STATION_NOT_FOUND = 'station_not_found';
    const TYPE_TICKET_CREATION_ERROR = 'ticket_creation_error';
    const TYPE_USER_CREDENTIALS_INVALID = 'user_credentials_invalid';
    const TYPE_CONNECTION_NOT_FOUND = 'connection_not_found';
    const TYPE_AVAILABILITY_ERROR = 'availability_error';
    const TYPE_PRICE_DISCREPANCY_ERROR = 'price_discrepancy_error';

    private static $titles = [
        self::TYPE_VALIDATION_ERROR => 'There was a validation error',
        self::TYPE_STATION_NOT_FOUND => 'There was no mapping found for a given station',
        self::TYPE_TICKET_CREATION_ERROR => 'There was an error during ticket creation',
        self::TYPE_USER_CREDENTIALS_INVALID => 'Your user credentials are invalid',
        self::TYPE_CONNECTION_NOT_FOUND => 'Connection between these stations is not found',
        self::TYPE_AVAILABILITY_ERROR => 'The number of vacant seats for the selected ride is not enough',
        self::TYPE_PRICE_DISCREPANCY_ERROR => 'The price discrepancy error'
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $title;

    /**
     * @var array
     */
    private $extraData = [];

    public function __construct($type, $statusCode = Response::HTTP_BAD_REQUEST)
    {
        $this->type = $type;
        $this->statusCode = $statusCode;

        if (!isset(self::$titles[$type])) {
            throw new \InvalidArgumentException('No title for type ' . $type);
        }

        $this->title = self::$titles[$type];
    }

    public function toArray()
    {
        return array_merge([
                'type' => $this->type,
                'title' => $this->title,
            ],
            $this->extraData
        );
    }

    public function set($name, $value)
    {
        $this->extraData[$name] = $value;

        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
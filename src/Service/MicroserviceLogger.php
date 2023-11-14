<?php

namespace App\Service;


use OldSound\RabbitMqBundle\RabbitMq\Producer;

use App\DTO\CrmLoggerDTO;

class MicroserviceLogger
{
    /**
     * @var boolean
     */
    private $isLoggerActive;

    /**
     * @var Producer
     */
    private $rabbitMqProducer;


    /**
     * MicroserviceLogger constructor.
     * @param $isLoggerActive
     * @param Producer $rabbitMqProducer
     */
    public function __construct(
        $isLoggerActive,
        Producer $rabbitMqProducer
    )
    {
        $this->isLoggerActive = $isLoggerActive;
        $this->rabbitMqProducer = $rabbitMqProducer;
    }

    /**
     * @param $event
     * @param $data
     * @param $sessionUniqueId
     * @param null $email
     * @throws \Exception
     */
    public function write($event, $data, $sessionUniqueId, $email = null): void
    {
        if (!$this->isLoggerActive) {

            return;
        }

        $this->send($event, $data, $sessionUniqueId, $email);
    }

    /**
     * @param $event
     * @param $data
     * @param $sessionUniqueId
     * @param $email
     * @return bool
     * @throws \Exception
     */
    protected function send($event, $data, $sessionUniqueId, $email): bool
    {
        $microserviceLogArray = [
            'sessionId' => $sessionUniqueId,
            'event' => $event,
            'data' => json_encode($data),
            'email' => $email,
            'date' => new \DateTime()
        ];

        $microserviceLog = CrmLoggerDTO::fromArray($microserviceLogArray);
        $rabbitMessage = $microserviceLog->toRabbiMqMessage();

        try {
            $this->rabbitMqProducer->setContentType('application/json');
            $this->rabbitMqProducer->publish($rabbitMessage);
        } catch (\Exception $e) {

            return false;
        }

        return true;
    }
}
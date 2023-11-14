<?php

namespace App\DTO;

abstract class RabbitMqMessageDTO
{
    const MESSAGE_TYPE_INDEX = 'MessageType';

    abstract public function toRabbiMqMessage();
}
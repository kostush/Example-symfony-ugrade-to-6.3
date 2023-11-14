<?php

namespace App\DTO;

class CrmLoggerDTO extends RabbitMqMessageDTO
{
    const MESSAGE_TYPE = 'CrmLogger';

    /**
     * @var string|null
     */
    private $sessionId;

    /**
     * @var string
     */
    private $event;

    /**
     * @var string
     */
    private $data;

    /**
     * @var string
     */
    private $email;

    /**
     * @var \DateTime
     */
    private $date;


    /**
     * CrmLoggerDTO constructor.
     * @param $sessionId
     * @param $event
     * @param $data
     * @param $email
     * @param $date
     */
    public function __construct(
        ?string $sessionId,
        string $event,
        string $data,
        ?string $email,
        \DateTime $date
    )
    {
        $this->sessionId = $sessionId;
        $this->event = $event;
        $this->data = $data;
        $this->email = $email;
        $this->date = $date->format('Y-m-d H:i:s');
    }

    /**
     * @return mixed
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param array $crmLogger
     * @return CrmLoggerDTO
     */
    public static function fromArray(array $crmLogger): self
    {
        return new self(
            $crmLogger['sessionId'],
            $crmLogger['event'],
            $crmLogger['data'],
            $crmLogger['email'],
            $crmLogger['date']
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $crmLoggerArray = array(
            'sessionId' => $this->getSessionId(),
            'event' => $this->getEvent(),
            'data' => $this->getData(),
            'email' => $this->getEmail(),
            'date' => $this->getDate()
        );

        return $crmLoggerArray;
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @return string
     */
    public function toRabbiMqMessage(): string
    {
        $array = $this->toArray();
        $array[self::MESSAGE_TYPE_INDEX] = self::MESSAGE_TYPE;

        return json_encode($array);
    }
}
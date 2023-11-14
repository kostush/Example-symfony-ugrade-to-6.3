<?php

namespace App\Service;

class RedisClient extends \Redis
{
    /**
     * @var string
     */
    private  $redisUser;

    /**
     * @var string
     */
    private  $redisPassword;

    /**
     * @var string
     */
    private  $redisHost;

    /**
     * @var int
     */
    private  $redisPort;

    /**
     * @var int
     */
    private  $redisExpirationTimeout;


    /**
     * RedisCache constructor
     * @param string $redisUser
     * @param string $redisPassword
     * @param string $redisHost
     * @param int $redisPort
     * @param int $redisExpirationTimeout
     */
    public function __construct(
        string $redisUser,
        string $redisPassword,
        string $redisHost,
        int $redisPort,
        int $redisExpirationTimeout,
        $options = null)
    {
        parent::__construct();
        $this->redisUser = $redisUser;
        $this->redisPassword = $redisPassword;
        $this->redisHost = $redisHost;
        $this->redisPort = $redisPort;
        $this->redisExpirationTimeout = $redisExpirationTimeout;



    }

    /**
     * @return bool
     * @throws \RedisException
     */
    public function init(){
        return $this->connect(
                    $this->redisHost,
                    $this->redisPort,
                    2.5,
                    null,
                    0,
                    0,
                     ['auth' => [$this->redisUser, $this->redisPassword]]
                );
    }

    /**
     * @return int
     */
    public function getRedisExpirationTimeout()
    {
        return $this->redisExpirationTimeout;
    }
}
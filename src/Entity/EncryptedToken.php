<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EncryptedTokenRepository")
 * @ORM\Table(name="encrypted_token")
 */
class EncryptedToken
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=false)
     */
    private $encryptedToken;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $attempt;


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return EncryptedToken
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEncryptedToken()
    {
        return $this->encryptedToken;
    }

    /**
     * @param mixed $encryptedToken
     * @return EncryptedToken
     */
    public function setEncryptedToken($encryptedToken): EncryptedToken
    {
        $this->encryptedToken = $encryptedToken;
        return $this;
    }

    /**
     * @param int $attempt
     * @return EncryptedToken
     */
    public function setAttempt(int $attempt): EncryptedToken
    {
        $this->attempt = $attempt;
        return $this;
    }

    /**
     * @return int
     */
    public function getAttempt(): int
    {
        return $this->attempt;
    }
}
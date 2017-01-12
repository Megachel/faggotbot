<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Chat
 *
 * @ORM\Table(name="chat")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ChatRepository")
 */
class Chat
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="chat_id", type="bigint", unique=true)
     */
    private $chatId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_pidor_time", type="datetime", nullable=true)
     */
    private $lastPidorTime;

    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="chats")
     * @ORM\JoinTable(name="chat_players")
     */
    private $players;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="last_pidor_id", referencedColumnName="id")
     */
    private $lastPidor;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set chatId
     *
     * @param integer $chatId
     *
     * @return Chat
     */
    public function setChatId($chatId)
    {
        $this->chatId = $chatId;

        return $this;
    }

    /**
     * Get chatId
     *
     * @return int
     */
    public function getChatId()
    {
        return $this->chatId;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Chat
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set lastPidorTime
     *
     * @param \DateTime $lastPidorTime
     *
     * @return Chat
     */
    public function setLastPidorTime($lastPidorTime)
    {
        $this->lastPidorTime = $lastPidorTime;

        return $this;
    }

    /**
     * Get lastPidorTime
     *
     * @return \DateTime
     */
    public function getLastPidorTime()
    {
        return $this->lastPidorTime;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
        $this->lastPidorTime = null;
    }

    /**
     * Add player
     *
     * @param \AppBundle\Entity\User $player
     *
     * @return Chat
     */
    public function addPlayer(\AppBundle\Entity\User $player)
    {
        $this->players[] = $player;

        return $this;
    }

    /**
     * Remove player
     *
     * @param \AppBundle\Entity\User $player
     */
    public function removePlayer(\AppBundle\Entity\User $player)
    {
        $this->players->removeElement($player);
    }

    /**
     * Get players
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * Set lastPidor
     *
     * @param \AppBundle\Entity\User $lastPidor
     *
     * @return Chat
     */
    public function setLastPidor(\AppBundle\Entity\User $lastPidor = null)
    {
        $this->lastPidor = $lastPidor;

        return $this;
    }

    /**
     * Get lastPidor
     *
     * @return \AppBundle\Entity\User
     */
    public function getLastPidor()
    {
        return $this->lastPidor;
    }
}

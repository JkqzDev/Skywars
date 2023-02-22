<?php

declare(strict_types=1);

namespace juqn\skywars\game;

use juqn\skywars\game\task\GameWaitingTask;
use juqn\skywars\session\Session;
use juqn\skywars\Skywars;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\Position;
use pocketmine\world\World;

final class Game {

    public const WAITING = 0;
    public const STARTING = 1;
    public const RUNNING = 2;
    public const ENDING = 3;

    /**
     * @param int $id
     * @param World $world
     * @param int $minPlayers
     * @param int $maxPlayers
     * @param int $state
     * @param Vector3[] $spawns
     * @param Player[] $players
     * @param TaskHandler|null $taskHandler
     */
    public function __construct(
        private int $id,
        private World $world,
        private int $minPlayers,
        private int $maxPlayers,
        private int $state = self::WAITING,
        private array $spawns = [],
        private array $players = [],
        private ?TaskHandler $taskHandler = null
    ) {
        $this->taskHandler = Skywars::getInstance()->getScheduler()->scheduleRepeatingTask(new GameWaitingTask($this, 60), 20);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getWorld(): World {
        return $this->world;
    }

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }

    public function getState(): int {
        return $this->state;
    }

    public function getSpawns(): array {
        return $this->spawns;
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function setState(int $state): void {
        $this->state = $state;
    }

    public function start(): void {
        $this->state = self::RUNNING;
        $this->taskHandler?->cancel();
    }

    public function finish(): void {
        $this->state = self::ENDING;
    }

    public function addPlayer(Session $session): bool {
        if ($session->getGame() !== null) {
            return false;
        }

        if ($this->state > self::STARTING) {
            return false;
        }

        if (count($this->players) >= $this->maxPlayers) {
            return false;
        }
        $session->setGame($this);

        $this->players[spl_object_hash($session->getPlayer())] = $session->getPlayer();
        return true;
    }

    public function removePlayer(Session $session): bool {
        $game = $session->getGame();

        if ($game === null || $game->getId() !== $this->id) {
            return false;
        }

        if (!isset($this->players[spl_object_hash($session->getPlayer())])) {
            $session->setGame(null);
            return false;
        }
        $session->setGame(null);
        unset($this->players[spl_object_hash($session->getPlayer())]);

        if ($this->state === self::STARTING && count($this->players) < $this->minPlayers) {
            /** @var GameWaitingTask|null $gameWaiting */
            $gameWaiting = $this->taskHandler?->getTask();
            $gameWaiting?->setStartQueue(60);
        }
        return true;
    }
}
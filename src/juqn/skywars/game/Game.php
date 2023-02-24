<?php

declare(strict_types=1);

namespace juqn\skywars\game;

use InvalidArgumentException;
use juqn\skywars\game\player\Player;
use juqn\skywars\game\player\PlayerManager;
use juqn\skywars\game\task\GameWaitingTask;
use juqn\skywars\session\Session;
use juqn\skywars\Skywars;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\Position;
use pocketmine\world\World;

final class Game {

    public const WAITING = 0;
    public const STARTING = 1;
    public const RUNNING = 2;
    public const ENDING = 3;

    private PlayerManager $playerManager;

    /**
     * @param World $world
     * @param int $heightLimiter
     * @param int $minPlayers
     * @param int $maxPlayers
     * @param int $state
     * @param Vector3[] $spawns
     * @param string[] $slots
     * @param TaskHandler|null $taskHandler
     */
    public function __construct(
        private World $world,
        private int $heightLimiter,
        private int $minPlayers,
        private int $maxPlayers,
        private int $state = self::WAITING,
        private array $spawns = [],
        private array $slots = [],
        private ?TaskHandler $taskHandler = null
    ) {
        $this->playerManager = new PlayerManager($this);
        $this->taskHandler = Skywars::getInstance()->getScheduler()->scheduleRepeatingTask(new GameWaitingTask($this, 60), 20);
    }

    public function getWorld(): World {
        return $this->world;
    }

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }

    public function getMinPlayers(): int {
        return $this->minPlayers;
    }

    public function getState(): int {
        return $this->state;
    }

    public function setState(int $state): void {
        $this->state = $state;
    }

    public function getPlayerManager(): PlayerManager {
        return $this->playerManager;
    }

    public function start(): void {
        $this->state = self::RUNNING;
        $this->taskHandler?->cancel();

        foreach ($this->spawns as $spawnIndex => $spawn) {
            if (isset($this->slots[$spawnIndex])) {
                $this->world->setBlock($spawn, VanillaBlocks::AIR());
            }
        }
    }

    public function finish(): void {
        $this->state = self::ENDING;
    }

    public function broadcast(string $message, int $type = 0): void {
        $players = array_filter($this->playerManager->getAll(), fn(Player $player) => $player->getInstance()->isOnline() && $player->isPlaying());

        foreach ($players as $player) {
            match ($type) {
                0 => $player->getInstance()->sendMessage($message),
                1 => $player->getInstance()->sendPopup($message),
                2 => $player->getInstance()->sendTip($message),
                3 => $player->getInstance()->sendActionBarMessage($message),
                default => throw new InvalidArgumentException('Invalid broadcast type')
            };
        }
    }

    public function checkWinner(): void {
        $players = array_values(array_filter($this->playerManager->getAll(), fn(Player $player) => $player->isPlaying() && !$player->isSpectator()));

        if (count($players) === 1) {
            $winner = $players[0];

            // Add win
        }
    }

    public function addPlayer(Session $session): bool {
        if ($session->getGame() !== null) {
            return false;
        }

        if ($this->state > self::STARTING) {
            return false;
        }
        $players = array_filter($this->playerManager->getAll(), fn(Player $player) => $player->getInstance()->isOnline() && $player->isPlaying());

        if (count($players) >= $this->maxPlayers) {
            return false;
        }
        $this->playerManager->add($session);
        $session->setGame($this);

        $slotIndex = 0;
        while (isset($this->slots[$slotIndex])) {
            $slotIndex++;
        }
        $this->slots[$slotIndex] = $session->getPlayer()->getXuid();
        $session->getPlayer()->teleport(Position::fromObject($this->spawns[$slotIndex], $this->world));

        if ($this->state === self::WAITING && count($this->playerManager->getAll()) >= $this->minPlayers) {
            $this->state = self::STARTING;
        }
        return true;
    }

    public function removePlayer(Session $session): bool {
        $game = $session->getGame();

        if ($game === null || $game->getWorld()->getFolderName() !== $this->getWorld()->getFolderName()) {
            return false;
        }

        if ($this->playerManager->get($session) === null) {
            $session->setGame(null);
            return false;
        }
        $this->playerManager->remove($session);
        $session->setGame(null);

        $slotIndex = array_search($session->getPlayer()->getXuid(), $this->slots);
        if ($slotIndex !== false) {
            unset($this->slots[$slotIndex]);
        }

        $players = array_filter($this->playerManager->getAll(), fn(Player $player) => $player->getInstance()->isOnline() && $player->isPlaying());
        if ($this->state === self::STARTING && count($players) < $this->minPlayers) {
            /** @var GameWaitingTask|null $gameWaiting */
            $gameWaiting = $this->taskHandler?->getTask();
            $gameWaiting?->setStartQueue(60);
        }
        return true;
    }
}
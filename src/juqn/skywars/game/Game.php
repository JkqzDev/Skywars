<?php

declare(strict_types=1);

namespace juqn\skywars\game;

use InvalidArgumentException;
use juqn\skywars\event\GameChangeStateEvent;
use juqn\skywars\event\GameStartEvent;
use juqn\skywars\event\GameStopEvent;
use juqn\skywars\event\player\PlayerDeathGame;
use juqn\skywars\event\player\PlayerJoinGame;
use juqn\skywars\event\player\PlayerQuitGame;
use juqn\skywars\event\player\PlayerWinGame;
use juqn\skywars\game\player\Player;
use juqn\skywars\game\player\PlayerManager;
use juqn\skywars\game\task\GameRunningTask;
use juqn\skywars\item\LeaveGame;
use juqn\skywars\session\Session;
use juqn\skywars\session\SessionFactory;
use juqn\skywars\Skywars;
use juqn\skywars\task\world\WorldCopyAsync;
use juqn\skywars\task\world\WorldDeleteAsync;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\World;

final class Game {

    public const WAITING = 0;
    public const STARTING = 1;
    public const RUNNING = 2;
    public const ENDING = 3;

    private PlayerManager $playerManager;
    private GameRunningTask $gameRunningTask;

    /**
     * @param World $world
     * @param int $heightLimiter
     * @param int $minPlayers
     * @param int $maxPlayers
     * @param int $state
     * @param Vector3[] $spawns
     * @param string[] $slots
     */
    public function __construct(
        private World $world,
        private int $heightLimiter,
        private int $minPlayers,
        private int $maxPlayers,
        private int $state = self::WAITING,
        private array $spawns = [],
        private array $slots = []
    ) {
        $this->playerManager = new PlayerManager($this);
        $this->gameRunningTask = new GameRunningTask($this, 60, 10);
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

    public function getGameRunningTask(): GameRunningTask {
        return $this->gameRunningTask;
    }

    public function getPlayerManager(): PlayerManager {
        return $this->playerManager;
    }

    public function setState(int $state): void {
        $this->state = $state;

        $event = new GameChangeStateEvent($this, $state);
        $event->call();
    }

    public function start(): void {
        $event = new GameStartEvent($this);
        $event->call();

        $this->setState(self::RUNNING);

        foreach ($this->playerManager->getAll() as $player) {
            if ($player->getInstance()->isOnline()) {
                $player->getInstance()->setImmobile(false);
            }
        }

        foreach ($this->spawns as $spawnIndex => $spawn) {
            if (isset($this->slots[$spawnIndex])) {
                $this->world->setBlock($spawn, VanillaBlocks::AIR());
            }
        }
    }

    public function stop(): void {
        $event = new GameStopEvent($this);
        $event->call();

        $this->setState(self::ENDING);
        $this->slots = [];

        $this->gameRunningTask->setStartQueue(60);
        $this->gameRunningTask->setStopQueue(10);

        foreach ($this->playerManager->getAll() as $player) {
            if ($player->getInstance()->isOnline()) {
                $event = new PlayerQuitGame($this, $player->getInstance());
                $event->call();
            }
        }
        $this->playerManager->reset();
        $worldName = $this->world->getFolderName();

        Server::getInstance()->getAsyncPool()->submitTask(new WorldDeleteAsync(
            worldName: $worldName,
            closure: function () use ($worldName): void {
                Server::getInstance()->getAsyncPool()->submitTask(new WorldCopyAsync(
                    worldName: $worldName,
                    closure: function (World $world): void {
                        Skywars::getInstance()->getLogger()->info('World from the game ' . $world->getFolderName() . ' is open.');
                    }
                ));
            }
        ));
    }

    public function broadcast(string $message, int $type = 0): void {
        if ($this->state === self::ENDING) {
            return;
        }
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
        /** @var Player[] $players */
        $players = array_values(array_filter($this->playerManager->getAll(), fn(Player $player) => $player->isPlaying() && !$player->isSpectator()));

        if (count($players) === 1) {
            $winner = $players[0];

            $event = new PlayerWinGame($this, $winner->getInstance());
            $event->call();

            $this->setState(self::ENDING);
        }
    }

    public function handleDeathGame(PlayerDeathGame $event): void {
        $player = $event->getPlayer();
        $damager = $event->getDamager();
        $game = $event->getGame();

        $session = SessionFactory::get($player);
        $session?->clear();

        $player->setGamemode(GameMode::SPECTATOR());

        if ($damager !== null) {
            $game->broadcast(TextFormat::colorize('&e' . $player->getName() . ' &7was killed by &e' . $damager->getName()));
        } else {
            $game->broadcast(TextFormat::colorize('&e' . $player->getName() . ' &7died!'));
        }
    }

    public function handleJoinGame(PlayerJoinGame $event): void {
        $player = $event->getPlayer();
        $game = $event->getGame();

        $session = SessionFactory::get($player);
        $session?->clear();

        $player->getInventory()->setContents([
            8 => new LeaveGame()
        ]);

        $game->broadcast(TextFormat::colorize('&7' . $player->getName() . ' &ehas join &7(&a' . count($game->getPlayerManager()->getAll()) . '&7/&a' . $game->getMaxPlayers() . '&7)'));
    }

    public function handleQuitGame(PlayerQuitGame $event): void {
        $player = $event->getPlayer();
        $game = $event->getGame();

        $session = SessionFactory::get($player);
        $session?->clear();

        $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());

        $game->broadcast(TextFormat::colorize('&7' . $player->getName() . ' &cquit!'));
    }

    public function handleWinGame(PlayerWinGame $event): void {
        $game = $event->getGame();
        $winner = $event->getWinner();

        // Code
    }

    public function handleBreak(BlockBreakEvent $event): void {
        if ($this->state <= self::STARTING) {
            $event->cancel();
        }
    }

    public function handlePlace(BlockPlaceEvent $event): void {
        $block = $event->getBlock();

        if ($this->state <= self::STARTING) {
            $event->cancel();
        } else {
            if ($block->getPosition()->getFloorY() >= $this->heightLimiter) {
                $event->cancel();
            }
        }
    }

    public function handleDamage(EntityDamageEvent $event): void {
        if ($this->state <= Game::STARTING) {
            $event->cancel();
            return;
        }
        $entity = $event->getEntity();

        if (!$entity instanceof \pocketmine\player\Player) {
            return;
        }
        $session = SessionFactory::get($entity);

        if ($session === null) {
            return;
        }
        $player = $this->playerManager->get($session);

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if (!$damager instanceof \pocketmine\player\Player) {
                return;
            }
            $target = SessionFactory::get($damager);

            if ($target === null) {
                return;
            }
            $target_player = $this->playerManager->get($target);

            if ($target->getGame() === null || $target->getGame()->getWorld()->getFolderName() !== $this->getWorld()->getFolderName()) {
                $event->cancel();
                return;
            }
            $player?->getCombat()->set($target_player);
        }
        $finalHealth = $entity->getHealth() - $event->getFinalDamage();

        if ($finalHealth <= 0.000 || $event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $event->cancel();
            $player?->setSpectator(true);

            if ($player !== null && $player->getCombat()->inCombat() && $player->getCombat()->getLastDamager() !== null) {
                $lastDamager = $player->getCombat()->getLastDamager();
                $this->playerManager->get($lastDamager->getInstance())?->addElimination();

                $event = new PlayerDeathGame($this, $entity, $player->getCombat()->getLastDamager()->getInstance());
            } else {
                $event = new PlayerDeathGame($this, $entity, null);
            }
            $event->call();
            $this->checkWinner();
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

        $event = new PlayerJoinGame($this, $session->getPlayer());
        $event->call();

        $slotIndex = 0;
        while (isset($this->slots[$slotIndex])) {
            $slotIndex++;
        }
        $this->slots[$slotIndex] = $session->getPlayer()->getXuid();
        $session->getPlayer()->teleport(Position::fromObject($this->spawns[$slotIndex]->add(0, 1, 0), $this->world));

        if ($this->state === self::WAITING && count($this->playerManager->getAll()) >= $this->minPlayers) {
            $this->setState(self::STARTING);
        } elseif ($this->state === self::STARTING && count($this->playerManager->getAll()) >= $this->maxPlayers && $this->gameRunningTask->getStartQueue() > 10) {
            $this->setState(self::RUNNING);
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

        $event = new PlayerQuitGame($this, $session->getPlayer());
        $event->call();

        $slotIndex = array_search($session->getPlayer()->getXuid(), $this->slots);
        if ($slotIndex !== false) {
            unset($this->slots[$slotIndex]);
        }

        $players = array_filter($this->playerManager->getAll(), fn(Player $player) => $player->isPlaying());
        if ($this->state === self::STARTING && count($players) < $this->minPlayers) {
            $this->gameRunningTask->setStartQueue(60);
            $this->setState(self::WAITING);
        }
        return true;
    }
}
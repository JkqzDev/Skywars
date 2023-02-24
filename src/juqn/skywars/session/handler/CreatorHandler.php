<?php

declare(strict_types=1);

namespace juqn\skywars\session\handler;

use juqn\skywars\game\GameFactory;
use juqn\skywars\session\Session;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

final class CreatorHandler {

    public function __construct(
        private Session $session,
        private World $world,
        private int $heightLimiter = World::Y_MAX,
        private int $minPlayers = 2,
        private int $maxPlayers = 12,
        private array $spawns = []
    ) {
        $this->prepare();
    }

    public function getWorld(): World {
        return $this->world;
    }

    public function getHeightLimiter(): int {
        return $this->heightLimiter;
    }

    public function getMinPlayers(): int {
        return $this->minPlayers;
    }

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }

    public function getSpawns(): array {
        return $this->spawns;
    }

    public function setHeightLimiter(int $heightLimiter): void {
        $this->heightLimiter = $heightLimiter;
    }

    public function setMinPlayers(int $minPlayers): void {
        $this->minPlayers = $minPlayers;
    }

    public function setMaxPlayers(int $maxPlayers): void {
        $this->maxPlayers = $maxPlayers;
    }

    public function addSpawn(int $spawnIndex, Vector3 $spawn): void {
        $this->spawns[$spawnIndex] = $spawn;
    }

    public function save(): void {
        $player = $this->session->getPlayer();

        if (count($this->spawns) !== $this->maxPlayers) {
            $player->sendMessage(TextFormat::colorize('&cYou need added all spawns.'));
            return;
        }
        $this->session->stopCreatorHandler();
        GameFactory::register($this->world, $this->heightLimiter, $this->minPlayers, $this->maxPlayers, $this->spawns);

        $player->sendMessage(TextFormat::colorize('&aYou have been created the skywars game ' . $this->world->getFolderName()));
        $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
    }

    public function prepare(): void {
        $player = $this->session->getPlayer();
        $player->setGamemode(GameMode::CREATIVE());
        $player->setFlying(true);
        $player->setAllowFlight(true);

        $player->teleport($this->world->getSpawnLocation());
    }

    public function reset(): void {
        $this->minPlayers = 2;
        $this->maxPlayers = 12;
        $this->spawns = [];
    }
}
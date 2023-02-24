<?php

declare(strict_types=1);

namespace juqn\skywars\session;

use juqn\skywars\game\Game;
use juqn\skywars\session\handler\CreatorHandler;
use pocketmine\player\Player;
use pocketmine\world\World;

final class Session {

    public function __construct(
        private Player $player,
        private ?Game $game = null,
        private ?CreatorHandler $creatorHandler = null
    ) {}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getGame(): ?Game {
        return $this->game;
    }

    public function getCreatorHandler(): ?CreatorHandler {
        return $this->creatorHandler;
    }

    public function setGame(?Game $game): void {
        $this->game = $game;
    }

    public function startCreatorHandler(World $world): void {
        $this->creatorHandler = new CreatorHandler($this, $world);
    }

    public function stopCreatorHandler(): void {
        $this->creatorHandler = null;
    }

    public function quit(): void {
        $this->game?->removePlayer($this);

        $this->game = null;
        $this->creatorHandler = null;
    }
}
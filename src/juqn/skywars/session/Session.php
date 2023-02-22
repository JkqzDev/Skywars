<?php

declare(strict_types=1);

namespace juqn\skywars\session;

use juqn\skywars\game\Game;
use pocketmine\player\Player;

final class Session {

    public function __construct(
        private Player $player,
        private ?Game $game = null
    ) {}

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getGame(): ?Game {
        return $this->game;
    }

    public function setGame(?Game $game): void {
        $this->game = $game;
    }
}
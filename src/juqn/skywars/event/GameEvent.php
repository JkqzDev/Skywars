<?php

declare(strict_types=1);

namespace juqn\skywars\event;

use juqn\skywars\game\Game;
use pocketmine\event\Event;

abstract class GameEvent extends Event {

    public function __construct(
        private Game $game
    ) {}

    public function getGame(): Game {
        return $this->game;
    }
}
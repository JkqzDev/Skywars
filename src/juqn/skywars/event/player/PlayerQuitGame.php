<?php

declare(strict_types=1);

namespace juqn\skywars\event\player;

use juqn\skywars\event\GameEvent;
use juqn\skywars\game\Game;
use pocketmine\player\Player;

final class PlayerQuitGame extends GameEvent {

    public function __construct(Game $game, private Player $player) {
        parent::__construct($game);
    }

    public function getPlayer(): Player {
        return $this->player;
    }
}
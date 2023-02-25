<?php

declare(strict_types=1);

namespace juqn\skywars\event\player;

use juqn\skywars\event\GameEvent;
use juqn\skywars\game\Game;
use pocketmine\player\Player;

final class PlayerWinGame extends GameEvent {

    public function __construct(
        Game $game,
        private Player $winner
    ) {
        parent::__construct($game);
    }

    public function getWinner(): Player {
        return $this->winner;
    }
}
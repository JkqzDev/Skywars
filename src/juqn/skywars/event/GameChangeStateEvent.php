<?php

declare(strict_types=1);

namespace juqn\skywars\event;

use juqn\skywars\game\Game;

final class GameChangeStateEvent extends GameEvent {

    public function __construct(Game $game, private int $state) {
        parent::__construct($game);
    }

    public function getState(): int {
        return $this->state;
    }
}
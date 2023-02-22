<?php

declare(strict_types=1);

namespace juqn\skywars\game\task;

use juqn\skywars\game\Game;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;

final class GameWaitingTask extends Task {

    public function __construct(
        private Game $game,
        private int $start_queue
    ) {}

    public function setStartQueue(int $start_queue): void {
        $this->start_queue = $start_queue;
    }

    public function onRun(): void {
        if ($this->game->getState() === Game::STARTING && --$this->start_queue <= 0) {
            $this->game->start();
            throw new CancelTaskException();
        }
    }
}
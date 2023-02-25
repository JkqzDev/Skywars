<?php

declare(strict_types=1);

namespace juqn\skywars\task;

use juqn\skywars\game\GameFactory;
use pocketmine\scheduler\Task;

final class GameTask extends Task {

    public function onRun(): void {
        foreach (GameFactory::getAll() as $game) {
            $game->getGameRunningTask()?->onRun();
        }
    }
}
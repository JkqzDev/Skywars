<?php

declare(strict_types=1);

namespace juqn\skywars\game\task;

use juqn\skywars\game\Game;
use juqn\skywars\game\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

final class GameWaitingTask extends Task {

    public function __construct(
        private Game $game,
        private int $start_queue
    ) {}

    public function setStartQueue(int $start_queue): void {
        $this->start_queue = $start_queue;
    }

    public function onRun(): void {
        $players = array_filter($this->game->getPlayerManager()->getAll(), fn(Player $player) => !$player->isSpectator() && $player->isPlaying());

        if ($this->game->getState() === Game::STARTING) {
            if ($this->start_queue <= 0) {
                $this->game->start();
                throw new CancelTaskException();
            }
            $this->game->broadcast(TextFormat::colorize('&eStarting game in ' . $this->start_queue . ' seconds'), 1);
        } else {
            $this->game->broadcast(TextFormat::colorize('&eWaiting ' . ($this->game->getMinPlayers() - count($players)) . ' players...'), 1);
        }
    }
}
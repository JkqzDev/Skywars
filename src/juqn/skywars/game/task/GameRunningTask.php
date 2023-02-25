<?php

declare(strict_types=1);

namespace juqn\skywars\game\task;

use juqn\skywars\game\Game;
use juqn\skywars\game\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

final class GameRunningTask extends Task {

    public function __construct(
        private Game $game,
        private int $start_queue,
        private int $stop_queue
    ) {}

    public function getStartQueue(): int {
        return $this->start_queue;
    }

    public function setStartQueue(int $start_queue): void {
        $this->start_queue = $start_queue;
    }

    public function setStopQueue(int $stop_queue): void {
        $this->stop_queue = $stop_queue;
    }

    public function onRun(): void {
        $players = array_filter($this->game->getPlayerManager()->getAll(), fn(Player $player) => !$player->isSpectator() && $player->isPlaying());

        if ($this->game->getState() === Game::STARTING) {
            if (--$this->start_queue <= 0) {
                $this->game->start();
                return;
            }
            $this->game->broadcast(TextFormat::colorize('&eStarting game in ' . $this->start_queue . ' seconds'), 1);
        } elseif ($this->game->getState() === Game::WAITING) {
            $this->game->broadcast(TextFormat::colorize('&eWaiting ' . ($this->game->getMinPlayers() - count($players)) . ' players...'), 1);
        } elseif ($this->game->getState() === Game::ENDING) {
            if (--$this->stop_queue <= 0) {
                $this->game->stop();
            }
        }
    }
}
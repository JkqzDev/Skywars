<?php

declare(strict_types=1);

namespace juqn\skywars\game\player;

use juqn\skywars\game\Game;
use juqn\skywars\session\Session;

final class PlayerManager {

    /** @var Player[] */
    private array $players = [];

    public function __construct(private Game $game) {}

    public function getAll(): array {
        return $this->players;
    }

    public function get(Session $session): ?Player {
        return $this->players[$session->getPlayer()->getXuid()] ?? null;
    }

    public function add(Session $session): Player {
        $this->players[$session->getPlayer()->getXuid()] = $player = new Player($session->getPlayer());
        return $player;
    }

    public function remove(Session $session): void {
        $player = $this->get($session);

        if ($player === null) {
            return;
        }

        if ($this->game->getState() <= Game::STARTING) {
            unset($this->players[$session->getPlayer()->getXuid()]);
            return;
        }
        $player->setPlaying(false);
    }

    public function reset(): void {
        $this->players = [];
    }
}
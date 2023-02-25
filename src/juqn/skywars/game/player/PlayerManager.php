<?php

declare(strict_types=1);

namespace juqn\skywars\game\player;

use juqn\skywars\game\Game;
use juqn\skywars\session\Session;

final class PlayerManager {

    /** @var Player[] */
    private array $players = [];

    public function __construct(
        private Game $game
    ) {}

    /**
     * @return Player[]
     */
    public function getAll(): array {
        return $this->players;
    }

    public function get(Session|\pocketmine\player\Player $session): ?Player {
        $xuid = $session instanceof Session ? $session->getPlayer()->getXuid() : $session->getXuid();
        return $this->players[$xuid] ?? null;
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
        $this->game->checkWinner();
    }

    public function reset(): void {
        $this->players = [];
    }
}
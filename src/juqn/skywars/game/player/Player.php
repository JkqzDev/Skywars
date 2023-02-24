<?php

declare(strict_types=1);

namespace juqn\skywars\game\player;

final class Player {

    public function __construct(
        private \pocketmine\player\Player $player,
        private int $eliminations = 0,
        private bool $spectator = false,
        private bool $playing = true
    ) {}

    public function getInstance(): \pocketmine\player\Player {
        return $this->player;
    }

    public function getEliminations(): int {
        return $this->eliminations;
    }

    public function isSpectator(): bool {
        return $this->spectator;
    }

    public function isPlaying(): bool {
        return $this->playing;
    }

    public function addElimination(): void {
        $this->eliminations++;
    }

    public function setSpectator(bool $spectator): void {
        $this->spectator = $spectator;
    }

    public function setPlaying(bool $playing): void {
        $this->playing = $playing;
    }
}
<?php

declare(strict_types=1);

namespace juqn\skywars\game\player\combat;

use juqn\skywars\game\player\Player;

final class Combat {

    public function __construct(private ?int $time = null, private ?Player $lastDamager = null) {}

    public function getLastDamager(): ?Player {
        return $this->lastDamager;
    }

    public function inCombat(): bool {
        return $this->time !== null && $this->time >= time();
    }

    public function set(?Player $player): void {
        if ($player === null) {
            return;
        }
        $this->lastDamager = $player;
        $this->time = time() + 15;
    }
}
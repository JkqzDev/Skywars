<?php

declare(strict_types=1);

namespace juqn\skywars\session;

use pocketmine\player\Player;

final class SessionFactory {

    /** @var Session[] */
    private static array $sessions = [];

    public static function register(Player $player): void {
        self::$sessions[$player->getXuid()] = new Session($player);
    }

    public static function remove(Player $player): void {
        if (self::get($player) === null) {
            return;
        }
        unset(self::$sessions[$player->getXuid()]);
    }

    public static function get(Player $player): ?Session {
        return self::$sessions[$player->getXuid()] ?? null;
    }
}
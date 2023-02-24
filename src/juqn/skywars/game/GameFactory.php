<?php

declare(strict_types=1);

namespace juqn\skywars\game;

use pocketmine\world\World;

final class GameFactory {

    /** @var Game[] */
    private static array $games = [];

    public static function getAll(): array {
        return self::$games;
    }

    public static function get(string $gameName): ?Game {
        return self::$games[$gameName] ?? null;
    }

    public static function register(World $world, int $heightLimiter, int $minPlayers, int $maxPlayers, array $spawns): Game {
        self::$games[$world->getFolderName()] = $game = new Game(
            world: $world,
            heightLimiter: $heightLimiter,
            minPlayers: $minPlayers,
            maxPlayers: $maxPlayers,
            spawns: $spawns
        );
        return $game;
    }

    public static function delete(string $gameName): void {
        if (self::get($gameName) === null) {
            return;
        }
        unset(self::$games[$gameName]);
    }
}
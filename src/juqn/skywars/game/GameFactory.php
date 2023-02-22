<?php

declare(strict_types=1);

namespace juqn\skywars\game;

final class GameFactory {

    /** @var Game[] */
    private static array $games = [];

    public static function getAll(): array {
        return self::$games;
    }

    public static function get(int $id): ?Game {
        return self::$games[$id] ?? null;
    }

    public static function register(): void {

    }

    public static function delete(int $id): void {
        if (self::get($id) === null) {
            return;
        }
        unset(self::$games[$id]);
    }
}
<?php

declare(strict_types=1);

namespace juqn\skywars\game;

use juqn\skywars\Skywars;
use pocketmine\utils\Config;
use pocketmine\world\World;
use RuntimeException;

final class GameFactory {

    /** @var Game[] */
    private static array $games = [];

    public static function register(World $world, int $heightLimiter, int $minPlayers, int $maxPlayers, array $spawns): Game {
        self::$games[$world->getFolderName()] = $game = new Game(world: $world, heightLimiter: $heightLimiter, minPlayers: $minPlayers, maxPlayers: $maxPlayers, spawns: $spawns);
        return $game;
    }

    public static function delete(string $gameName): void {
        if (self::get($gameName) === null) {
            return;
        }
        unset(self::$games[$gameName]);
    }

    public static function get(string $gameName): ?Game {
        return self::$games[$gameName] ?? null;
    }

    public static function load(): void {
        if (!is_dir(Skywars::getInstance()->getDataFolder() . 'worlds')) {
            @mkdir(Skywars::getInstance()->getDataFolder() . 'worlds');
        }

        if (!is_dir(Skywars::getInstance()->getDataFolder() . 'games')) {
            @mkdir(Skywars::getInstance()->getDataFolder() . 'games');
        }
        $files = glob(Skywars::getInstance()->getDataFolder() . 'games' . DIRECTORY_SEPARATOR . '*.json');

        foreach ($files as $file) {
            $config = new Config($file, Config::JSON);

            try {
                self::$games[basename($file, '.json')] = Game::deserializeData(basename($file, '.json'), $config->getAll());
            } catch (RuntimeException $exception) {
                Skywars::getInstance()->getLogger()->error('[Game ' . basename($file, '.json') . '] ' . $exception->getMessage());
            }
        }
    }

    public static function getAll(): array {
        return self::$games;
    }

    /**
     * @throws \JsonException
     */
    public static function save(): void {
        if (!is_dir(Skywars::getInstance()->getDataFolder() . 'games')) {
            @mkdir(Skywars::getInstance()->getDataFolder() . 'games');
        }

        foreach (self::getAll() as $gameName => $game) {
            $config = new Config(Skywars::getInstance()->getDataFolder() . 'games' . DIRECTORY_SEPARATOR . $gameName . '.json', Config::JSON);
            $config->setAll($game->serializeData());
            $config->save();
        }
    }
}
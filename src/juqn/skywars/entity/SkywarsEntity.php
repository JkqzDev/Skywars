<?php

declare(strict_types=1);

namespace juqn\skywars\entity;

use juqn\skywars\game\Game;
use juqn\skywars\game\GameFactory;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;

final class SkywarsEntity extends Human {

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        if ($hasUpdate) {
            /** @var Game[] $games */
            $games = array_values(array_filter(GameFactory::getAll(), fn(Game $game) => $game->getState() < Game::RUNNING && count($game->getPlayerManager()->getAll()) < $game->getMaxPlayers()));
            uasort($games, fn(Game $firstGame, Game $secondGame) => count($firstGame->getPlayerManager()->getAll()) > count($secondGame->getPlayerManager()->getAll()));

            if (count($games) === 0) {
                $this->setNameTag(TextFormat::colorize('&7× &eSkyWars v1.0 &7×' . PHP_EOL . '&cGames not available'));
            } else {
                $game = $games[0];

                $this->setNameTag(TextFormat::colorize('&7× &eSkyWars v1.0 &7×' . PHP_EOL . '&eGame: &6' . $game->getWorld()->getFolderName() . PHP_EOL . '&ePlayers: &6' . count($game->getPlayerManager()->getAll()) . '/' . $game->getMaxPlayers()));
            }
        }
        return $hasUpdate;
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $this->setImmobile();
        $this->setHealth(200);

        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();
    }
}
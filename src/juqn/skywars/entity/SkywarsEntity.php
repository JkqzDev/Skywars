<?php

declare(strict_types=1);

namespace juqn\skywars\entity;

use juqn\skywars\game\Game;
use juqn\skywars\game\GameFactory;
use juqn\skywars\session\SessionFactory;
use juqn\skywars\Skywars;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class SkywarsEntity extends Human {

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);
        /** @var Game[] $games */
        $games = array_values(array_filter(GameFactory::getAll(), fn(Game $game) => $game->getState() < Game::RUNNING && count($game->getPlayerManager()->getAll()) < $game->getMaxPlayers()));
        uasort($games, fn(Game $firstGame, Game $secondGame) => count($firstGame->getPlayerManager()->getAll()) > count($secondGame->getPlayerManager()->getAll()));

        if (count($games) === 0) {
            $this->setNameTag(TextFormat::colorize('&7× &eSkyWars v1.0 &7×' . PHP_EOL . '&cGames not available'));
        } else {
            $game = $games[0];

            $this->setNameTag(TextFormat::colorize('&7× &eSkyWars v1.0 &7×' . PHP_EOL . '&eGame: &6' . $game->getWorld()->getFolderName() . PHP_EOL . '&ePlayers: &6' . count($game->getPlayerManager()->getAll()) . '/' . $game->getMaxPlayers()));
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

        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            if (!$damager instanceof Player) {
                return;
            }
            $itemInHand = $damager->getInventory()->getItemInHand();

            if ($itemInHand->getId() === ItemIds::BEDROCK && $damager->getServer()->isOp($damager->getName())) {
                $this->flagForDespawn();
                return;
            }
            $session = SessionFactory::get($damager);

            if ($session === null) {
                return;
            }
            /** @var Game[] $games */
            $games = array_values(array_filter(GameFactory::getAll(), fn(Game $game) => $game->getState() < Game::RUNNING && count($game->getPlayerManager()->getAll()) < $game->getMaxPlayers()));
            uasort($games, fn(Game $firstGame, Game $secondGame) => count($firstGame->getPlayerManager()->getAll()) > count($secondGame->getPlayerManager()->getAll()));

            if (count($games) === 0) {
                $damager->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cGames not available.'));
                return;
            }
            $game = $games[0];

            if ($game->addPlayer($session)) {
                $damager->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&aGood luck!'));
            } else {
                $damager->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cYou can\'t join. Try again.'));
            }
        }
    }
}
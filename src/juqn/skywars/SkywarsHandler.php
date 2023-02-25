<?php

declare(strict_types=1);

namespace juqn\skywars;

use juqn\skywars\event\player\PlayerDeathGame;
use juqn\skywars\event\player\PlayerJoinGame;
use juqn\skywars\event\player\PlayerQuitGame;
use juqn\skywars\event\player\PlayerWinGame;
use juqn\skywars\session\SessionFactory;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

final class SkywarsHandler implements Listener {

    public function handleDeathGame(PlayerDeathGame $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $session?->getGame()?->handleDeathGame($event);
    }

    public function handleJoinGame(PlayerJoinGame $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $session?->getGame()?->handleJoinGame($event);
    }

    public function handleQuitGame(PlayerQuitGame $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $session?->getGame()?->handleQuitGame($event);
    }

    public function handleWinGame(PlayerWinGame $event): void {
        $player = $event->getWinner();
        $session = SessionFactory::get($player);
        $session?->getGame()?->handleWinGame($event);
    }

    public function handleBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        $session?->getGame()?->handleBreak($event);
    }

    public function handlePlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        $session?->getGame()?->handlePlace($event);
    }

    public function handleDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();

        if (!$entity instanceof Player) {
            return;
        }
        $session = SessionFactory::get($entity);

        $session?->getGame()?->handleDamage($event);
    }

    public function handleChat(PlayerChatEvent $event): void {
        $message = $event->getMessage();
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        if ($session === null) {
            return;
        }
        $creatorHandler = $session->getCreatorHandler();

        if ($creatorHandler === null) {
            return;
        }
        $args = explode(' ', $message);

        switch (strtolower($args[0])) {
            case '?':
            case 'help':
                break;

            case 'save':
                $event->cancel();

                $creatorHandler->save();
                break;

            case 'height_limiter':
                $event->cancel();

                $position_y = $player->getPosition()->subtract(0, 1, 0)->getFloorY();

                if ($position_y <= World::Y_MIN && $position_y > World::Y_MAX) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid height limiter.'));
                    return;
                }
                $creatorHandler->setHeightLimiter($position_y);
                $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&aYou have been set height limiter in y=' . $position_y));
                break;

            case 'min_players':
                $event->cancel();

                if (!isset($args[1])) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cUse minPlayers [number]'));
                    return;
                }

                if (!is_numeric($args[1])) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid number.'));
                    return;
                }
                $minPlayers = (int) $args[1];

                if ($minPlayers < 2 || $minPlayers >= $creatorHandler->getMaxPlayers()) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid min players.'));
                    return;
                }
                $creatorHandler->setMinPlayers($minPlayers);
                $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&aYou have been set min players in ' . $minPlayers));
                break;

            case 'max_players':
                $event->cancel();

                if (!isset($args[1])) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cUse maxPlayers [number]'));
                    return;
                }

                if (!is_numeric($args[1])) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid number.'));
                    return;
                }
                $maxPlayers = (int) $args[1];

                if ($maxPlayers <= $creatorHandler->getMinPlayers() || $maxPlayers > 64) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid max players.'));
                    return;
                }
                $creatorHandler->setMaxPlayers($maxPlayers);
                $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&aYou have been set max players in ' . $maxPlayers));
                break;

            case 'spawn':
                $event->cancel();

                if (!isset($args[1])) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cUse spawn [number]'));
                    return;
                }

                if (!is_numeric($args[1])) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid number.'));
                    return;
                }
                $slotIndex = (int) $args[1];

                if ($slotIndex <= 0 || $slotIndex > $creatorHandler->getMaxPlayers()) {
                    $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cInvalid spawn index.'));
                    return;
                }
                $creatorHandler->addSpawn($slotIndex - 1, $player->getPosition()->asVector3()->subtract(0, 1, 0)->floor()->add(0.5, 0, 0.5));
                $player->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&aSpawn #' . $slotIndex . ' added.'));
                break;
        }
    }

    public function handleJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        if ($session === null) {
            SessionFactory::register($player);
        }
    }

    public function handleQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $session?->getGame()?->removePlayer($session);

        SessionFactory::remove($player);
    }
}
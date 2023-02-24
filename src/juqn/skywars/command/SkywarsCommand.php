<?php

declare(strict_types=1);

namespace juqn\skywars\command;

use juqn\skywars\game\Game;
use juqn\skywars\game\GameFactory;
use juqn\skywars\session\SessionFactory;
use juqn\skywars\Skywars;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class SkywarsCommand extends Command {

    public function __construct() {
        parent::__construct('skywars', 'Use command to skywars settings.');
        $this->setPermission('skywars.command');
        $this->setAliases(['sw']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::colorize('&cUse command in game.'));
            return;
        }
        $session = SessionFactory::get($sender);

        if ($session === null) {
            $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cERROR'));
            return;
        }

        if (count($args) < 0) {
            $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cUse /skywars help'));
            return;
        }

        switch (strtolower($args[0])) {
            case 'help':
            case '?':
                break;

            case 'create':
                if ($session->getCreatorHandler() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou have already creator.'));
                    return;
                }

                if (!isset($args[1])) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /skywars create [world]'));
                    return;
                }
                $worldName = $args[1];

                if (!$sender->getServer()->getWorldManager()->isWorldGenerated($worldName)) {
                    $sender->sendMessage(TextFormat::colorize('&cWorld not exists.'));
                    return;
                }

                if (!$sender->getServer()->getWorldManager()->isWorldLoaded($worldName)) {
                    $sender->getServer()->getWorldManager()->loadWorld($worldName);
                }
                $session->startCreatorHandler($sender->getServer()->getWorldManager()->getWorldByName($worldName));
                $sender->sendMessage(TextFormat::colorize('&aUse \'?\' for help'));
                break;

            ///////////////////////////////////////////// COMMANDS FOR TEST PLUGIN /////////////////////////////////////////////
            case 'join':
                if ($session->getCreatorHandler() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t join.'));
                    return;
                }
                /** @var Game[] $games */
                $games = array_values(array_filter(GameFactory::getAll(), fn(Game $game) => $game->getState() < Game::RUNNING && count($game->getPlayerManager()->getAll()) < $game->getMaxPlayers()));
                uasort($games, fn(Game $firstGame, Game $secondGame) => count($firstGame->getPlayerManager()->getAll()) > count($secondGame->getPlayerManager()->getAll()));

                if (count($games) === 0) {
                    $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cGames not available.'));
                    return;
                }
                $game = $games[0];

                if ($game->addPlayer($session)) {
                    $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&aGood luck!'));
                } else {
                    $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cYou can\'t join. Try again.'));
                }
                break;

            case 'quit':
                $game = $session->getGame();

                if ($game === null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t play a game'));
                    return;
                }
                $game->removePlayer($session);
                $sender->sendMessage(TextFormat::colorize('&cYou quit game'));
                break;
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            default:
                $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cSubcommand not exists. Use /skywars help'));
                break;
        }
    }
}
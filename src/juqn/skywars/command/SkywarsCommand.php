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

            case 'join':
                /** @var Game[] $games */
                $games = array_values(array_filter(GameFactory::getAll(), fn(Game $game) => $game->getState() < Game::RUNNING && count($game->getPlayers()) < $game->getMaxPlayers()));
                uasort($games, fn(Game $firstGame, Game $secondGame) => count($firstGame->getPlayers()) > count($secondGame->getPlayers()));

                if (count($games) === 0) {
                    $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cGames not available.'));
                    return;
                }
                $game = $games[0];
                $game->addPlayer($session);
                $session->setGame($game);
                
                break;

            default:
                $sender->sendMessage(TextFormat::colorize(Skywars::PREFIX . '&cSubcommand not exists. Use /skywars help'));
                break;
        }
    }
}
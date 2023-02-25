<?php

declare(strict_types=1);

namespace juqn\skywars\item;

use juqn\skywars\session\SessionFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

final class LeaveGame extends SkywarsItem {

    public function __construct() {
        parent::__construct(ItemIds::BED, '&r&cLeave game');
    }

    public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult {
        $session = SessionFactory::get($player);

        if ($session === null) {
            return ItemUseResult::FAIL();
        }
        $session->getGame()?->removePlayer($session);
        return ItemUseResult::SUCCESS();
    }
}
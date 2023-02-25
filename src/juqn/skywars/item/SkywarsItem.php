<?php

declare(strict_types=1);

namespace juqn\skywars\item;

use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\utils\TextFormat;

class SkywarsItem extends Item {

    public function __construct(int $id, string $name, int $meta = 0) {
        parent::__construct(new ItemIdentifier($id, $meta), $name);
        $this->setCustomName(TextFormat::colorize($name));
    }
}
<?php

declare(strict_types=1);

namespace juqn\skywars;

use juqn\skywars\command\SkywarsCommand;
use juqn\skywars\entity\SkywarsEntity;
use juqn\skywars\task\GameTask;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

final class Skywars extends PluginBase {
    use SingletonTrait;

    public const PREFIX = '&eSkywars &8>> ';

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        /////////////////////////////////////////////////
        if (!is_dir($this->getDataFolder() . 'worlds')) {
            @mkdir($this->getDataFolder() . 'worlds');
        }
        /////////////////////////////////////////////////
        $this->getServer()->getCommandMap()->register('Skywars', new SkywarsCommand());
        /////////////////////////////////////////////////
        $this->getServer()->getPluginManager()->registerEvents(new SkywarsHandler(), $this);
        /////////////////////////////////////////////////
        $this->getScheduler()->scheduleRepeatingTask(new GameTask(), 20);
        ////////////////////////////////////////////////
        EntityFactory::getInstance()->register(SkywarsEntity::class, function (CompoundTag $nbt, World $world): SkywarsEntity {
            return new SkywarsEntity(EntityDataHelper::parseLocation($nbt, $world), SkywarsEntity::parseSkinNBT($nbt), $nbt);
        }, ['SkywarsEntity']);
    }
}
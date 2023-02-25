<?php

declare(strict_types=1);

namespace juqn\skywars;

use JsonException;
use juqn\skywars\command\SkywarsCommand;
use juqn\skywars\entity\SkywarsEntity;
use juqn\skywars\game\GameFactory;
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
        $this->registerCommand();
        $this->registerHandler();
        $this->registerTaskHandler();
        $this->registerEntity();

        GameFactory::load();
    }

    private function registerCommand(): void {
        $this->getServer()->getCommandMap()->register('Skywars', new SkywarsCommand());
    }

    private function registerHandler(): void {
        $this->getServer()->getPluginManager()->registerEvents(new SkywarsHandler(), $this);
    }

    private function registerTaskHandler(): void {
        $this->getScheduler()->scheduleRepeatingTask(new GameTask(), 20);
    }

    private function registerEntity(): void {
        EntityFactory::getInstance()->register(SkywarsEntity::class, function (World $world, CompoundTag $nbt): SkywarsEntity {
            return new SkywarsEntity(EntityDataHelper::parseLocation($nbt, $world), SkywarsEntity::parseSkinNBT($nbt), $nbt);
        }, ['SkywarsEntity']);
    }

    /**
     * @throws JsonException
     */
    protected function onDisable(): void {
        GameFactory::save();
    }
}
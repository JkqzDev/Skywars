<?php

declare(strict_types=1);

namespace juqn\skywars\task\world;

use juqn\skywars\Skywars;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

final class WorldCopyAsync extends AsyncTask {

    private string $serverWorldDirectory;
    private string $pluginWorldDirectory;

    public function __construct(
        private string $worldName,
        private bool $copyToServerWorld = true,
        private ?\Closure $closure = null
    ) {
        $this->serverWorldDirectory = Server::getInstance()->getDataPath() . 'worlds' . DIRECTORY_SEPARATOR;
        $this->pluginWorldDirectory = Skywars::getInstance()->getDataFolder() . 'worlds' . DIRECTORY_SEPARATOR;
    }

    public function onRun(): void {
        if ($this->copyToServerWorld) {
            $this->copySource($this->pluginWorldDirectory . $this->worldName, $this->serverWorldDirectory . $this->worldName);
        } else {
            $this->copySource($this->serverWorldDirectory . $this->worldName, $this->pluginWorldDirectory . $this->worldName);
        }
    }

    public function onCompletion(): void {
        $closure = $this->closure;

        if ($closure !== null) {
            Server::getInstance()->getWorldManager()->loadWorld($this->worldName);
            $closure(Server::getInstance()->getWorldManager()->getWorldByName($this->worldName));
        }
    }

    private function copySource(string $source, string $target): void {
        if (!is_dir($source)) {
            @copy($source, $target);
            return;
        }
        @mkdir($target);
        $dir = dir($source);

        while (($entry = $dir->read()) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $Entry = $source . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($Entry)) {
                $this->copySource($Entry, $target . DIRECTORY_SEPARATOR . $entry);
                continue;
            }
            @copy($Entry, $target . DIRECTORY_SEPARATOR . $entry);
        }
        $dir->close();
    }
}
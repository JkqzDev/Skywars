<?php

declare(strict_types=1);

namespace juqn\skywars\task\world;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

final class WorldDeleteAsync extends AsyncTask {

    private string $serverWorldDirectory;

    public function __construct(
        private string $worldName,
        private ?\Closure $closure = null
    ) {
        $this->serverWorldDirectory = Server::getInstance()->getDataPath() . 'worlds' . DIRECTORY_SEPARATOR;
    }

    public function onRun(): void {
        $this->deleteSource($this->serverWorldDirectory . $this->worldName);
    }

    public function onCompletion(): void {
        $closure = $this->closure;

        if ($closure !== null) {
            $closure();
        }
    }

    private function deleteSource(string $source): void {
        if (!is_dir($source)) {
            return;
        }

        if ($source[strlen($source) - 1] !== '/') {
            $source .= '/';
        }

        /** @var array $files */
        $files = glob($source . '*', GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteSource($file);
            } else {
                unlink($file);
            }
        }
        rmdir($source);
    }
}
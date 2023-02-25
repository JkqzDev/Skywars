<?php

declare(strict_types=1);

namespace juqn\skywars\util;

use pocketmine\math\Vector3;

final class Math {

    public static function vectorToString(Vector3 $vector3): string {
        return $vector3->getX() . ':' . $vector3->getY() . ':' . $vector3->getZ();
    }

    public static function stringToVector(string $data): Vector3 {
        $data = explode(':', $data);

        if (count($data) !== 3) {
            throw new \RuntimeException('Invalid vector data');
        }
        return new Vector3((float) $data[0], (float) $data[1], (float) $data[2]);
    }
}
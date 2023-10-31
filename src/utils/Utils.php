<?php

/*
 *       _      _           _                ___   ___ ____
 *      (_)    | |         | |              / _ \ / _ \___ \
 * _ __  _  ___| |__   ___ | | __ _ ___ ___| | | | | | |__) |
 *| '_ \| |/ __| '_ \ / _ \| |/ _` / __/ __| | | | | | |__ <
 *| | | | | (__| | | | (_) | | (_| \__ \__ \ |_| | |_| |__) |
 *|_| |_|_|\___|_| |_|\___/|_|\__,_|___/___/\___/ \___/____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author nicholass003
 * @link https://github.com/nicholass003/
 *
 */

declare(strict_types=1);

namespace nicholass003\softsell\utils;

use pocketmine\player\Player;

class Utils{
    public const TYPE_NONE = 0;
    public const TYPE_AUTO = 1;
    public const TYPE_MANUAL = 2;

    public const PLUGIN_PREFIX = "§eSoftSell§f: ";

    /** @var int[] */
    public static array $sellType = [];

    public static function getSellActive(Player $player) : int{
        return self::$sellType[$player->getName()];
    }

    public static function setSellActive(Player $player, int $type) : void{
        self::$sellType[$player->getName()] = $type;
    }

    public static function isSellActive(Player $player) : bool{
        return self::$sellType[$player->getName()] !== self::TYPE_NONE;
    }

    public static function unsetSellActive(Player $player) : void{
        unset(self::$sellType[$player->getName()]);
        self::$sellType[$player->getName()] = self::TYPE_NONE;
    }

    public static function getNumericType(mixed $value) : float|int{
        return is_int($value) ? intval($value) : floatval($value);
    }

    public static function getItemParserName(string $name) : string{
        return strtolower(str_replace(" ", "_", $name));
    }
}
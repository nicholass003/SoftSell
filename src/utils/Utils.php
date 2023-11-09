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

use nicholass003\softsell\Main;
use pocketmine\block\Block;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class Utils{

    public const TYPE_NONE = 0;
    public const TYPE_AUTO = 1;
    public const TYPE_MANUAL = 2;
    public const TYPE_INVENTORY = 3;

    public const PLUGIN_PREFIX = "§f[§eSoftSell§f] ";

    public static array $allType = ["Auto", "Inventory", "Manual"];

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

    public static function matchSellType(int $type) : string{
        return match($type){
            self::TYPE_NONE => "None",
            self::TYPE_AUTO => "Auto",
            self::TYPE_INVENTORY => "Inventory",
            self::TYPE_MANUAL => "Manual",
            default => "None"
        };
    }

    public static function getSubCommandDescription(string $subCommand) : string{
        return match($subCommand){
            "add" => "To setup item price in your hands",
            "auto" => "Change SoftSell type to Automatic Sell",
            "help" => "Show help information",
            "inventory" => "Change SoftSell type to Inventory Sell",
            "items" => "Show item price list",
            "list" => "Show SoftSell type list",
            "manual" => "Change SoftSell type to Manual Sell",
            "reset" => "Change SoftSell type to None",
            "status" => "Show SoftSell status"
        };
    }

    public static function getNumericType(mixed $value) : float|int{
        return is_int($value) ? intval($value) : floatval($value);
    }

    public static function getItemParserName(string $name) : string{
        return strtolower(str_replace(" ", "_", $name));
    }

    public static function getMessage(string $message) : string{
        return Main::getInstance()->getConfig()->get($message);
    }

    public static function isWithMessage() : bool{
        return Main::getInstance()->getConfig()->get("with-message");
    }

    public static function getItemName(string $name) : string{
        $item = StringToItemParser::getInstance()->parse($name);
        if($item === null) return "";
        return $item->getVanillaName();
    }

    public static function performSellTransaction(Player $player, ?Block $block = null) : bool{
        $totalPrice = 0;
        if(self::getSellActive($player) === self::TYPE_AUTO){
            if($block === null) return false;
            $price = Main::getInstance()->getDatabaseProvider()->getPrice(self::getItemParserName($block->asItem()->getVanillaName()));
            if($price === 0) return false;
            $totalPrice = $price;
        }elseif(self::getSellActive($player) === self::TYPE_MANUAL){
            $item = $player->getInventory()->getItemInHand();
            $price = Main::getInstance()->getDatabaseProvider()->getPrice(self::getItemParserName($item->getVanillaName()));
            if($price === 0) return false;
            $totalPrice = $price * $item->getCount();
            $player->getInventory()->setItemInHand(VanillaItems::AIR());
        }elseif(self::getSellActive($player) === self::TYPE_INVENTORY){
            $price = 0;
            foreach($player->getInventory()->getContents() as $slot => $item){
                $price = Main::getInstance()->getDatabaseProvider()->getPrice(self::getItemParserName($item->getVanillaName())) * $item->getCount();
                if($price === 0) continue;
                $totalPrice += $price;
                $player->getInventory()->setItem($slot, VanillaItems::AIR());
            }
            if($totalPrice === 0) return false;
        }
        if($totalPrice === 0) return false;
        Main::getInstance()->getEconomyProvider()->addPlayerMoney($player, $totalPrice);
        if(self::isWithMessage()){
            $player->sendMessage(str_replace("{revenue}", (string) $totalPrice, self::getMessage("sell-message")));
        }
        return true;
    }
}
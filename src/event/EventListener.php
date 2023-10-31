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

namespace nicholass003\softsell\event;

use nicholass003\softsell\Main;
use nicholass003\softsell\utils\Utils;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class EventListener implements Listener{

    public function __construct(private Main $plugin){
        //NOOP
    }

    public function onPlayerJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        Utils::setSellActive($player, Utils::TYPE_NONE);
    }

    public function onPlayerQuit(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        unset(Utils::$sellType[$player->getName()]);
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        if(Utils::getSellActive($player) === Utils::TYPE_AUTO){
            $block = $event->getBlock();
            $price = $this->plugin->getDatabaseProvider()->getPrice(Utils::getItemParserName($block->asItem()->getVanillaName()));
            if($price === 0) return;
            $event->setDrops([VanillaItems::AIR()]);
            $this->plugin->getEconomyProvider()->addPlayerMoney($player, $price);
            $player->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "+" . $price . " balance");
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        $player = $event->getPlayer();
        if(Utils::getSellActive($player) === Utils::TYPE_MANUAL){
            $item = $event->getItem();
            $price = $this->plugin->getDatabaseProvider()->getPrice(Utils::getItemParserName($item->getVanillaName()));
            if($price === 0) return;
            $player->getInventory()->setItemInHand(VanillaItems::AIR());
            $this->plugin->getEconomyProvider()->addPlayerMoney($player, $price * $item->getCount());
            $player->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "+" . $price * $item->getCount() . " balance");
            $event->cancel();
        }
    }
}
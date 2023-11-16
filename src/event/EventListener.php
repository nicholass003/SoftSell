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

class EventListener implements Listener{

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
        if(Utils::performSellTransaction($player, $event->getBlock())){
            $event->setDrops([VanillaItems::AIR()]);
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        $player = $event->getPlayer();
        if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) return;
        if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            if(Utils::performSellTransaction($player)){
                $event->cancel();
            }
        }
    }
}
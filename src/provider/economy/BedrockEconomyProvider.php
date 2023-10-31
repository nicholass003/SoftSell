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

namespace nicholass003\softsell\provider\economy;

use nicholass003\softsell\provider\EconomyProvider;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;
use pocketmine\player\Player;

class BedrockEconomyProvider extends EconomyProvider{

    public function addPlayerMoney(Player $player, float|int $amount) : void{
        $balance = $amount;
        $decimals = 0;

        if(is_float($amount)){
            $amount = explode(".", (string)$amount);
    
            $balance = (int)$amount[0];
            $decimals = (int)$amount[1] ?? 0;
        }

        BedrockEconomyAPI::ASYNC()->add($player->getXuid(), $player->getName(), $balance, $decimals);
    }

    public function checkClass() : bool{
        return class_exists(BedrockEconomyAPI::class) ? true : false;
    }

    public function getName() : string{
        return "BedrockEconomy";
    }
}
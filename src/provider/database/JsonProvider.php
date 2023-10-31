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

namespace nicholass003\softsell\provider\database;

use nicholass003\softsell\Main;
use nicholass003\softsell\provider\DatabaseProvider;
use pocketmine\utils\Config;

class JsonProvider extends DatabaseProvider{

    public function __construct(private Main $plugin){
        $this->database = new Config($plugin->getDataFolder() . "prices.json", Config::JSON);
    }

    public function getType() : string{
        return "JSON";
    }

    public function getPrice(string $name) : float|int{
        return $this->database->exists($name) ? $this->database->get($name) : 0;
    }

    public function setPrice(string $name, float|int $value) : void{
        $this->database->set($name, $value);
        $this->database->save();
    }
}
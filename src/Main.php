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

namespace nicholass003\softsell;

use nicholass003\softsell\event\EventListener;
use nicholass003\softsell\provider\database\JsonProvider;
use nicholass003\softsell\provider\database\SQLite3Provider;
use nicholass003\softsell\provider\DatabaseProvider;
use nicholass003\softsell\provider\economy\BedrockEconomyProvider;
use nicholass003\softsell\provider\economy\EconomyAPIProvider;
use nicholass003\softsell\provider\EconomyProvider;
use nicholass003\softsell\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class Main extends PluginBase{
    use SingletonTrait;

    /** @var EconomyProvider|null */
    public ?EconomyProvider $economyProvider;

    public DatabaseProvider $databaseProvider;

    protected function onLoad() : void{
        $this->saveDefaultConfig();
    }

    protected function onEnable() : void{
        self::setInstance($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->databaseProvider = match(strtolower($this->getConfig()->get("database-provider"))){
            "sqlite3" => new SQLite3Provider($this),
            "json" => new JsonProvider($this),
            default => new JsonProvider($this)
        };

        $this->economyProvider = match(strtolower($this->getConfig()->get("economy-provider"))){
            "bedrockeconomy" => new BedrockEconomyProvider(),
            "economyapi" => new EconomyAPIProvider(),
            default => null
        };

        if($this->economyProvider === null){
            $this->error($this->getConfig()->get("economy-provider"));
            return;
        }elseif(!$this->economyProvider->checkClass()){
            $this->error($this->economyProvider->getName());
            return;
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($sender instanceof Player){
            if($command->getName() === "softsell"){
                if(isset($args[0])){
                    switch(strtolower($args[0])){
                        case "add":
                            if(isset($args[1]) && is_numeric($args[1]) && $sender->hasPermission("softsell.command.admin")){
                                $database = $this->getDatabaseProvider();
                                $item = $sender->getInventory()->getItemInHand();
                                $database->setPrice(Utils::getItemParserName($item->getVanillaName()), Utils::getNumericType($args[1]));
                                $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "Success added " . $item->getVanillaName() . " with price: " . $args[1] );
                            }else{
                                $sender->sendMessage(TextFormat::RED. "Usage: /softsell add <price>");
                            }
                            return true;
                        case "auto":
                            Utils::setSellActive($sender, Utils::TYPE_AUTO);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "SoftSell have set to Auto");
                            return true;
                        case "hand":
                        case "manual":
                            Utils::setSellActive($sender, Utils::TYPE_MANUAL);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN. "SoftSell have set to Manual");
                            return true;
                        case "off":
                        case "reset":
                            Utils::unsetSellActive($sender);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN. "SoftSell have set to None");
                            return true;
                        default:
                            $sender->sendMessage("Usage: /softsell <auto:manual:reset>");
                            return true;
                    }
                }else{
                    $sender->sendMessage("Usage: /softsell <auto:manual>");
                    return true;
                }
            }
        }else{
            $sender->sendMessage(TextFormat::RED . "You must be logged in.");
            return true;
        }
        return false;
    }

    public function getDatabaseProvider() : DatabaseProvider{
        return $this->databaseProvider;
    }

    public function getEconomyProvider() : ?EconomyProvider{
        return $this->economyProvider;
    }

    private function error(string $name) : void{
        $this->getLogger()->error("The Economy Provider class $name does not exist");
        $this->getServer()->getPluginManager()->disablePlugin($this);
    }
}
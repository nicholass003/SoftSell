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

    public static array $subCommands = ["add", "auto", "help", "inventory", "items", "list", "manual", "reset", "status"];

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
                                $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "Success added " . $item->getVanillaName() . " with price: " . $args[1]);
                            }else{
                                if(!$sender->hasPermission("softsell.command.admin")){
                                    $sender->sendMessage(TextFormat::RED . "You are not allowed to use this command");
                                    return true;
                                }
                                $sender->sendMessage(TextFormat::RED. "Usage: /softsell add <price>");
                            }
                            return true;
                        case "auto":
                            Utils::setSellActive($sender, Utils::TYPE_AUTO);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "have set to Auto");
                            return true;
                        case "help":
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "Help Information");
                            foreach(self::$subCommands as $subCommand){
                                $sender->sendMessage("- " . TextFormat::GREEN . $subCommand . " " . TextFormat::YELLOW . Utils::getSubCommandDescription($subCommand));
                            }
                            return true;
                        case "inv":
                        case "inventory":
                            Utils::setSellActive($sender, Utils::TYPE_INVENTORY);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "have set to Inventory");
                            return true;
                        case "prices":
                        case "items":
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "Item Information");
                            $database = $this->getDatabaseProvider();
                            foreach($database->getProducts() as $name => $price){
                                $sender->sendMessage("- " . TextFormat::YELLOW . Utils::getItemName($name) . " price: " . $price . "\n");
                            }
                            return true;
                        case "type":
                        case "list":
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "Type Information");
                            foreach(Utils::$allType as $type){
                                $sender->sendMessage("- ". TextFormat::YELLOW . $type . "\n");
                            }
                            return true;
                        case "hand":
                        case "manual":
                            Utils::setSellActive($sender, Utils::TYPE_MANUAL);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "have set to Manual");
                            return true;
                        case "off":
                        case "reset":
                            Utils::unsetSellActive($sender);
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "have set to None");
                            return true;
                        case "info":
                        case "status":
                            $sender->sendMessage(Utils::PLUGIN_PREFIX . TextFormat::GREEN . "Status: " . TextFormat::RESET . Utils::matchSellType(Utils::getSellActive($sender)));
                            return true;
                        default:
                            $sender->sendMessage("Usage: /softsell <auto:inventory:items:list:manual:reset:status>");
                            return true;
                    }
                }else{
                    if($sender->hasPermission("softsell.command.admin")){
                        $sender->sendMessage("Usage: /softsell <add:auto:inventory:items:list:manual:reset:status>");
                        return true;
                    }
                    $sender->sendMessage("Usage: /softsell <auto:inventory:items:list:manual:reset:status>");
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
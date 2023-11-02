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

use Exception;
use nicholass003\softsell\Main;
use nicholass003\softsell\provider\DatabaseProvider;
use SQLite3;

class SQLite3Provider extends DatabaseProvider{

    public function __construct(private Main $plugin){
        $this->database = new SQLite3($plugin->getDataFolder() . 'prices.db');
        $this->database->exec('CREATE TABLE IF NOT EXISTS prices (item TEXT PRIMARY KEY, price NUMERIC);');
        $this->database->enableExceptions(true);
    }

    public function getType() : string{
        return "SQLite3";
    }

    public function getPrice(string $name) : float|int{
        $prepare = $this->database->prepare('SELECT price FROM prices WHERE item = :name;');
        $prepare->bindValue(':name', $name, SQLITE3_TEXT);
        $result = $prepare->execute();
        if($result){
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if($row !== false && isset($row['price'])){
                return $row['price'];
            }
        }
        return 0;
    }

    public function setPrice(string $name, float|int $value) : void{
        $this->database->exec('BEGIN TRANSACTION;');
        try{
            $price = $this->getPrice($name);
            if($price !== 0){
                $this->updatePrice($name, $value);
            }else{
                $prepare = $this->database->prepare('INSERT INTO prices (item, price) VALUES (:name, :value);');
                $prepare->bindValue(':name', $name, SQLITE3_TEXT);
                $prepare->bindValue(':value', $value, is_float($value) ? SQLITE3_FLOAT : SQLITE3_INTEGER);
                if($prepare->execute() === false){
                    throw new Exception("Failed to insert price");
                }
            }
        }catch(Exception $e){
            throw $e;
        }finally{
            $this->endTransaction();
        }
    }

    private function updatePrice(string $name, float|int $value) : void{
        $this->database->exec('BEGIN TRANSACTION;');
        try{
            $prepare = $this->database->prepare('UPDATE prices SET price = :value WHERE item = :name;');
            $prepare->bindValue(':name', $name, SQLITE3_TEXT);
            $prepare->bindValue(':value', $value, is_float($value) ? SQLITE3_FLOAT : SQLITE3_INTEGER);
            if($prepare->execute() === false){
                throw new Exception('Failed to update prices');
            }
        }catch(Exception $e){
            throw $e;
        }finally{
            $this->endTransaction();
        }
    }

    private function endTransaction() : void{
        try{
            $this->database->exec('COMMIT;');
        }catch(Exception $e){
            $this->database->exec('ROLLBACK;');
            throw $e;
        }
    }
}
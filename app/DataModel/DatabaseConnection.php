<?php

namespace App\DataModel;

class DatabaseConnection
{
    private static ?\PDO $instance = null;

    public static function getInstance(): \PDO
    {
        if (self::$instance === null) {
            self::$instance = new \PDO(
                'mysql:host=localhost:3306;dbname=novelcamp;charset=utf8',
                'sa',
                '***'
            );
            self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return self::$instance;
    }
}
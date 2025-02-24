<?php
namespace Deneb\PhpBaas\Database;

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DriverManager;

class Connection
{
    private static ?DBALConnection $connection = null;

    public static function getInstance(): DBALConnection
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/database.php';
            self::$connection = DriverManager::getConnection($config);
        }

        return self::$connection;
    }
}

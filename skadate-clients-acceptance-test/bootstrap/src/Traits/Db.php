<?php

namespace Skadate\Traits;

use PDO;
use Exception;

trait Db
{
    /**
     * Pdo
     * @var PDO
     */
    private static $pdo;

    /**
     * Db name
     * @var string
     */
    private $dbName = '';

    /**
     * Db host
     * @var string
     */
    private $dbHost = '';

    /**
     * Db user
     * @var string
     */
    private $dbUser = '';

    /**
     * Db password
     * @var string
     */
    private $dbPassword = '';

    /**
     * Db table prefix
     * @var string
     */
    private $dbTablePrefix = '';

    /**
     * Set db params
     * 
     * @param string $dbName
     * @param string $dbHost
     * @param string $dbUser
     * @param string $dbPassword
     * @return void
     */
    protected function setDbParams($dbName, $dbHost, $dbUser, $dbPassword, $dbTablePrefix) {
        $this->dbName = $dbName;
        $this->dbHost = $dbHost;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->dbTablePrefix = $dbTablePrefix;
    }

    /**
     * Get pdo
     * 
     * @return PDO
     */
    private function getPdo() {
        if (self::$pdo) {
            return self::$pdo;
        }

        $dsn = "mysql:host={$this->dbHost}:3306;dbname={$this->dbName};charset=utf8";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        self::$pdo = new PDO($dsn, $this->dbUser, $this->dbPassword, $options);

        return self::$pdo;
    }

    /**
     * Execute sql file
     *
     * @param string $path
     * @param array $replace
     * @return void
     */
    protected function executeSqlFile($path, array $replace = [])
    {
        $pdo = $this->getPdo();

        $replace = [
            'from' => array_merge([
                '__prefix__',
                '__db_name__'
            ], (!empty($replace) ? array_keys($replace) : [])),
            'to' => array_merge([
                $this->dbTablePrefix,
                $this->dbName
            ], (!empty($replace) ? array_values($replace) : []))
        ];

        if (!file_exists($path) || !($handler = fopen($path, 'r'))) {
            throw new Exception('Install sql file not found or permission denied');
        }
 
        $query = null;
        $delimiter = ';';
 
        // collect all queries
        while(!feof($handler)) {
            $str = trim(fgets($handler));

            if(empty($str) || $str[0] == '' || $str[0] == '#' || ($str[0] == '-' && $str[1] == '-'))  {
                continue;
            }

            // change delimiter
            if(strpos($str, 'DELIMITER //') !== false || strpos($str, 'DELIMITER ;') !== false) {
                $delimiter = trim(str_replace('DELIMITER', '', $str));
                continue;
            }

            $query .= ' ' . $str;

            // check for multi line query
            if(substr($str, -strlen($delimiter)) != $delimiter) {
                continue;
            }

            // execute query
            if (!empty($replace['from']) && !empty($replace['to'])) {
                $query = str_replace($replace['from'], $replace['to'], $query);
            }

            if ($delimiter != ';') {
                $query = str_replace($delimiter, '', $query);
            }

            $pdo->query(trim($query));
            $query = null;
        }

        fclose($handler);
    }
}

<?php

class Database 
{
    public $host;
    public $port;
    public $user;
    public $pass;
    public $db;

    protected static $instance;

    protected function __construct($host, $port, $user, $pass, $db)
    {
        $this->db = $this->getInstance($host, $port, $user, $pass, $db);
    }

    public static function getInstance($host, $port, $user, $pass, $db) {
        try 
        {
            self::$instance = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            self::$instance->query("SET NAMES 'UTF8'");
            self::$instance->query("SET CHARACTER SET 'UTF8'");
        } 
        catch(PDOException $error) 
        {
            var_dump($error);

            exit;
        }

        return self::$instance;
    }

    public static function setCharsetEncoding()
    {
        self::$instance->exec("SET NAMES 'UTF8'");
    }
}
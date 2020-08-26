<?php

namespace SCBot\Configuration;

/**  Setup Error Reporting */
ini_set('error_reporting', E_ALL|E_STRICT);
ini_set('display_errors', 1);

class Configuration
{
    public $dbName;
    public $dbUser;
    public $dbPassword;
    public $dbHost;
    public $dbCharset;
    public $dbCollate;

    function __construct($dbName, $dbUser, $dbPassword, $dbHost,
                         $dbCharset = "utf-8", $dbCollate = "") {
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->dbHost = $dbHost;
        $this->dbCharset = $dbCharset;
        $this->dbCollate = $dbCollate;
    }

    /**
     * Load the configuration from a file
     */
    public static function loadFromFile($filename) {
        $settings = parse_ini_file($filename);
        return new Configuration(
            $settings['DB_NAME'],
            $settings['DB_USER'],
            $settings['DB_PASSWORD'],
            $settings['DB_HOST'],
            $settings['DB_CHARSET'],
            $settings['DB_COLLATE']
        );
    }

    // Heroku forces you to use env variables
    public static function loadFromEnvVars(){
        return new Configuration(
            getenv('DB_NAME', ''),
            getenv('DB_USER', ''),
            getenv('DB_PASSWORD', ''),
            getenv('DB_HOST', ''),
            getenv('DB_CHARSET', ''),
            getenv('DB_COLLATE', '')
        );
    }
}
?>

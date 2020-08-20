<?php
require_once('database.php');
require_once('preferences.php');
require_once('helpers.php');
use SCBot\Database\DatabaseQuery;
use SCBot\Configuration\Configuration;
use SCBot\Preferences\Preferences;

// setup db connection on first connection
if (!array_key_exists("db", $GLOBALS)
    || !array_key_exists("preferences", $GLOBALS)) {
    $conf = Configuration::loadFromFile("bot.conf");
    $db = DatabaseQuery::fromConfig($conf);
    $GLOBALS['db'] = $db;
    $GLOBALS['preferences'] = $db->getPreferences();
    date_default_timezone_set('UTC');
}
?>

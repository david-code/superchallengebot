<?php

require_once('configuration.php');

use scbot\configuration\Configuration;
use scbot\database\Database;
use scbot\preferences\Preferences;
use scbot\update\Updater;

$config = Configuration::loadFromFile('bot.conf');
var_dump($config);
/*
$conn = Database::connect($config);
$pref = $conn->loadPreferences();
$updater = new Updater($pref);
$updater->update();
*/

?>

<?php

require_once('configuration.php');

use SCBot\Configuration\Configuration;
use SCBot\Database\DatabaseQuery;
use SCBot\Twitter\Twitter;

$config = Configuration::loadFromFile('bot.conf');

try {
   $db = DatabaseQuery::from($config);
   $pref = $conn->loadPreferences();
   $twit = Twitter::fromPreferences($pref);
   $updater = new Updater($pref, $conn, $twit);
   $updater->update();
} catch (Exception $e) {
   die($e->message);
}

?>

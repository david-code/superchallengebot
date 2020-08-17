<?php

require_once('configuration.php');
require_once('database.php');
require_once('twitter.php');
require_once('update.php');

use SCBot\Configuration\Configuration;
use SCBot\Database\DatabaseQuery;
use SCBot\Twitter\Twitter;
use SCBot\Update\Updater;

$config = Configuration::loadFromFile('bot.conf');

try {
   $db = DatabaseQuery::fromConfig($config);
   $pref = $db->getPreferences();
   $twit = Twitter::fromPreferences($pref);
   $updater = new Updater($db, $pref, $twit, getenv('SCBOT_TESTING', true));
   $updater->update();
} catch (Exception $e) {
   die($e->message);
}

?>

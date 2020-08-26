<?php

require_once('configuration.php');
require_once('database.php');
require_once('twitter.php');
require_once('update.php');

use SCBot\Configuration\Configuration;
use SCBot\Database\DatabaseQuery;
use SCBot\Twitter\Twitter;
use SCBot\Update\Updater;

try {
    $testing = getenv('SCBOT_TESTING', true);
    $config = Configuration::loadConfig();
    $db = DatabaseQuery::fromConfig($config);
    $pref = $db->getPreferences();
    $twit = Twitter::fromPreferences($pref, $testing);
    $updater = new Updater($db, $pref, $twit, $testing);
    $updater->update();
} catch (Exception $e) {
    die($e->getMessage());
}

?>

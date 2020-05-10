<?php

require_once('configuration.php');

use SCBot\Configuration\Configuration;

$config = Configuration::loadFromFile('bot.conf');
/*
try {
   $db = DatabaseQuery::from($config);
   $pref = $conn->loadPreferences();
   $updater = new Updater($pref, $conn, $twit);
   $updater->update();
} catch (Exception $e) {
   die($e->message);
}
*/

?>

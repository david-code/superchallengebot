<?php

require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;


$logger = new Logger("superchallengebot");

$logger->pushHandler(new StreamHandler("php://stdout", Logger::DEBUG));

?>

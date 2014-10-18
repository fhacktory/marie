<?php

error_reporting(-1);
require_once 'vendor/autoload.php';

$app = new \Slim\Slim(['debug' => true]);
$app->run();

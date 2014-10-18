<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\ThePirateBay\Scraper;

error_reporting(-1);
require_once 'vendor/autoload.php';

$app = new \Slim\Slim(['debug' => true]);

$app->get('/', function()  {
    phpinfo();
});

$app->group('/api', function() use ($app) {
    $app->get('/torrent/search/:query', function($query) {
        $torrents = Scraper::search(
            $query,
            Scraper::CAT_VIDEO,
            Scraper::ORDER_SEEDERS_DESC
        );

        echo json_encode($torrents);
    });
});

$app->run();
header('Content-Type: application/json');

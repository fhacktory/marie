<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\ThePirateBay\Scraper;

error_reporting(-1);
require_once 'vendor/autoload.php';

$app = new \Slim\Slim(['debug' => true]);
$app->hook('slim.after.router', function () use ($app) {
    if(strpos($app->request->getResourceUri(), '/api/') === 0)
        $app->response->headers->set('Content-Type', 'application/json');
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

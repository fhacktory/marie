<?php

namespace Duchesse\Chaton\Marie;

error_reporting(-1);
require_once 'vendor/autoload.php';


$app = new \Slim\Slim(['debug' => false]);
$app->hook('slim.after.router', function() use ($app) {
    if (strpos($app->request->getResourceUri(), '/api/') === 0)
        $app->response->headers->set('Content-Type', 'application/json');
});

$app->error(function($e) use ($app) {
    $app->response->headers->set('Content-Type', 'application/json');
    echo json_encode([
        'data' => null,
        'meta' => [
            'success' => false,
            'messages' => [$e->getMessage()]
        ],
    ]);
});

$app->group('/api', function() use ($app) {
    $controller = new Controller($app);
    $app->get('/movie/list',            [$controller, 'movieList']);
    $app->get('/movie/:imdbId',         [$controller, 'movieGet']);
    $app->get('/torrent/search/:query', [$controller, 'torrentSearch']);
});

$app->run();

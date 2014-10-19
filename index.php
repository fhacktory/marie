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

$app->get('/', function() {
    header('Location: //trolls.cat');
    exit();
});

$app->group('/api', function() use ($app) {
    $controller = new Controller($app);
    $app->get('/movie/list',              [$controller, 'movieList']);
    $app->get('/movie/stream/:imdbId',    [$controller, 'movieGetStream']);
    $app->get('/movie/:imdbId(/:create)', [$controller, 'movieGet']);
    $app->get('/torrent/search/:query',   [$controller, 'torrentSearch']);
    $app->get('/status',                  [$controller, 'status']);
});

$app->run();

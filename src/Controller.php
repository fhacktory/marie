<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\ThePirateBay\Scraper;
use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\Models\Movie;

class Controller
{
    /**
     * @var \Slim\Slim
     */
    protected $app;

    /**
     * @var EntityManager
     */
    protected $em;

    protected $data;
    protected $meta = [
        'success' => true,
        'messages' => [],
    ];

    public function __construct($app)
    {
        $this->app = $app;
        $this->em = Util::getEntityManager();
    }

    protected function out()
    {
        echo json_encode([
            'meta' => $this->meta,
            'data' => $this->data
        ]);
    }

    protected function msg($msg)
    {
        $this->meta->messages[] = $msg;
    }

    protected function error($msg)
    {
        $this->msg($msg);
        $this->meta->success = false;
    }

    public function movieList()
    {
        $movies =
            $this->em
            ->createQuery('SELECT m FROM Marie:Movie m')
            ->getArrayResult()
        ;

        $this->data = compact('movies');
        $this->out();
    }

    public function movieGet($imdbId)
    {
        $get = function($imdbId) {
            return
                $this->em
                ->createQuery('SELECT m FROM Marie:Movie m where m.imdbId = :imdbId')
                ->setParameter('imdbId', $imdbId)
                ->getArrayResult()
            ;
        };
        $movies = $get($imdbId);

        if (!count($movies)) {
            $this->movieCreate($imdbId);
            $movies = $get($imdbId);
            if (!count($movies))
                throw new \RuntimeException('Unable to fetch requested movie.');
        }

        $this->data = compact('movies');
        $this->out();
    }

    protected function movieCreate($imdbId)
    {
        $movie = new Movie();
        $movie->setImdbId($imdbId);
        $movie->refreshFromImdb();
        $movie->refreshFromTpb();
        $this->em->persist($movie);
        $this->em->flush();
    }

    public function torrentSearch($query)
    {
        $torrents = Scraper::search(
            $query,
            Scraper::CAT_VIDEO,
            Scraper::ORDER_SEEDERS_DESC
        );

        $this->data = compact('torrents');
        $this->out();
    }
}

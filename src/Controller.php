<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\ThePirateBay\Scraper;
use Duchesse\Chaton\Marie\Util;

class Controller
{
    protected $data;
    protected $meta = [
        'success' => true,
        'messages' => [],
    ];

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
            Util::getEntityManager()
            ->createQuery('SELECT m FROM Marie:Movie m')
            ->getArrayResult()
        ;

        $this->data = compact('movies');
        $this->out();
    }

    public function movieGet($imdbId)
    {
        $movies =
            Util::getEntityManager()
            ->createQuery('SELECT m FROM Marie:Movie m where m.imdbId = :imdbId')
            ->setParameter('imdbId', $imdbId)
            ->getArrayResult()
        ;

        $this->data = compact('movies');
        $this->out();
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

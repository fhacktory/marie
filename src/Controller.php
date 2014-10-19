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

    public function movieGetStream($imdbId)
    {
        $movie = $this->em->getRepository('Marie:Movie')->find($imdbId);
        if ($movie === null)
            throw new \InvalidArgumentException('Unknown movie.');

        if ($movie->status !== Movie::STATUS_CACHED)
            throw new \InvalidArgumentException('Not in cache.');

        $this->data = [
            'movies' => [[
                'imdbId' => $imdbId,
                'stream' => $movie->getStreamUrl()
            ]],
        ];
        $this->out();
    }

    public function movieGet($imdbId, $create = false)
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

        if (!count($movies) && $create) {
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

    public function status()
    {
        $api = Util::getTransmissionApi();
        $freeSpace = (int) $api->getFreeSpace()->getSize();
        $s = $api->getSessionStats();

        $stats = [
            'activeTorrentCount' => (int) $s->getActiveTorrentCount(),
            'downloadSpeed'      => (int) $s->getDownloadSpeed(),
            'pausedTorrentCount' => (int) $s->getPausedTorrentCount(),
            'torrentCount'       => (int) $s->getTorrentCount(),
            'uploadSpeed'        => (int) $s->getUploadSpeed(),

            'current' => [
                'downloadedBytes' => (int) $s->getCurrent()->getDownloadedBytes(),
                'filesAdded'      => (int) $s->getCurrent()->getFilesAdded(),
                'secondsActive'   => (int) $s->getCurrent()->getSecondsActive(),
                'sessionCount'    => (int) $s->getCurrent()->getSessionCount(),
                'uploadedBytes'   => (int) $s->getCurrent()->getUploadedBytes(),
            ],

            'cumulative' => [
                'downloadedBytes' => (int) $s->getCumulative()->getDownloadedBytes(),
                'filesAdded'      => (int) $s->getCumulative()->getFilesAdded(),
                'secondsActive'   => (int) $s->getCumulative()->getSecondsActive(),
                'sessionCount'    => (int) $s->getCumulative()->getSessionCount(),
                'uploadedBytes'   => (int) $s->getCumulative()->getUploadedBytes(),
            ],
        ];

        $this->data = compact('freeSpace', 'stats');
        $this->out();
    }

    public function movieGif($imdbId, $start, $stop, $quality, $text = null)
    {
        $movie = $this->em->getRepository('Marie:Movie')->find($imdbId);
        if ($movie === null)
            throw new \InvalidArgumentException('Unknown movie.');

        if ($movie->status !== Movie::STATUS_CACHED)
            throw new \InvalidArgumentException('Not in cache.');

        $process = new Processor($imdbId);
        $path = $process('gif', compact('start', 'stop', 'quality', 'text'));
        $this->data = [
            'movies' => [[
                'imdbId' => $imdbId,
                'gif' => Util::buildUrl($path),
            ]],
        ];

        $this->out();
    }
}

<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\Models\Movie;
use Transmission\Client;
use Transmission\Transmission;

class TransmissionDaemon
{
    protected $api;
    protected $em;

    public function __construct($host, $port, $user = null, $pass = null)
    {
        $client = new Client();
        if ($pass !== null)
            $client->authenticate($user, $pass);

        $this->api = new Transmission($host, $port);
        $this->api->setClient($client);

        $this->em = Util::getEntityManager();
    }

    public function __invoke()
    {
        syslog(LOG_INFO, 'Starting TransmissionDaemon.');
        for (;;) {
            try {
                $this->tick();
            } catch (\RuntimeException $e) {
                syslog(LOG_ERR, $e->getMessage());
            }
            sleep(1);
        }
    }

    protected function tick()
    {
        foreach ($this->getMovies() as $movie) {
            switch ($movie->status) {
                case Movie::STATUS_NOT_CACHED:
                    $this->startDownload($movie);
                    break;
                case Movie::STATUS_DOWNLOADING:
                    $this->updateDownloading($movie);
                    break;
                case Movie::STATUS_PENDING_PROCESSING:
                    // TODO
                    break;
                case Movie::STATUS_PROCESSING:
                    // TODO
                    break;
                case Movie::STATUS_CACHED:
                    // dafuq?
                    break;
                default:
                    assert('false /* unreachable */');
            }
        }
    }

    protected function updateDownloading(Movie $movie)
    {
        $torrent = $this->api->get($movie->torrentHash);
        $movie->downloadProgress = (int) $torrent->getPercentDone();
        syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - downloading {$movie->downloadProgress}% (ETA {$torrent->getEta()})");
        if($torrent->isFinished()) {
            syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - finished");
            $this->api->stop($torrent);
            $movie->downloadProgress = null;
            $movie->status = Movie::STATUS_PENDING_PROCESSING;
        }

        $this->em->flush();
    }

    protected function startDownload(Movie $movie)
    {
        assert('strlen($movie->magnet)');
        try {
            $this->api->get($movie->torrentHash);
            return; // already started
        } catch (\RuntimeException $e) {
            // NOP
        }

        syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - start download");
        $torrent = $this->api->add($movie->magnet);
        $this->api->start($torrent);
        $movie->status = Movie::STATUS_DOWNLOADING;
        $movie->downloadProgress = 0;
        $this->em->flush();
    }

    protected function getMovies()
    {
        return
            $this->em
            ->createQuery('SELECT m FROM Marie:Movie m WHERE m.status != :status')
            ->setParameter('status', Movie::STATUS_CACHED)
            ->getResult()
        ;
    }
}

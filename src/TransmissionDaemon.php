<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\Models\Movie;

class TransmissionDaemon
{
    protected $api;
    protected $em;

    public function __construct()
    {
        $this->api = Util::getTransmissionApi();
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
        $movie->progress = (int) $torrent->getPercentDone();
        $movie->eta = (int) $torrent->getEta();
        syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - downloading {$movie->progress}% (ETA {$movie->eta})");
        if($torrent->isFinished()) {
            syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - finished");
            $this->api->stop($torrent);
            $movie->progress = null;
            $movie->eta = null;
            $movie->status = Movie::STATUS_PENDING_PROCESSING;
        }

        $this->em->flush();
    }

    protected function startDownload(Movie $movie)
    {
        assert('strlen($movie->magnet)');
        try {
            $this->api->get($movie->torrentHash);
            $movie->status = Movie::STATUS_DOWNLOADING;
            $movie->progress = 0;
            $movie->eta = -2;
            $this->em->flush();
            return; // already started
        } catch (\RuntimeException $e) {
            // NOP
        }

        syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - start download");
        $torrent = $this->api->add($movie->magnet);
        $this->api->start($torrent);
        $movie->status = Movie::STATUS_DOWNLOADING;
        $movie->progress = 0;
        $movie->eta = -2;
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

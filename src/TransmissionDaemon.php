<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\Models\Movie;

class TransmissionDaemon
{
    protected $api;
    protected $em;
    protected $proc;

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
                    if ($this->proc === null)
                        $this->process($movie);
                    break;
                case Movie::STATUS_PROCESSING:
                    if ($this->proc === null)
                        throw new \RuntimeException("Movie #{$movie->imdbId} processing but no proc.");
                    $this->updateProcessing($movie);
                    break;
                case Movie::STATUS_CACHED:
                    break;
                default:
                    assert('false /* unreachable */');
            }
        }
    }

    protected function process(Movie $movie)
    {
        assert('$this->proc === null');
        assert('$movie->status === \Duchesse\Chaton\Marie\Models\Movie::STATUS_PENDING_PROCESSING');
        syslog(LOG_INFO, "Starting process on #{$movie->imdbId}.");

        $bin = realpath(dirname(__DIR__)) . '/process';

        $this->proc = popen($bin . ' ' . escapeshellarg($movie->imdbId), 'r');
        $movie->status = Movie::STATUS_PROCESSING;
        $movie->progress = 0;
        $this->em->flush();
    }

    protected function updateProcessing(Movie $movie)
    {
        if (feof($this->proc)) {
            syslog(LOG_INFO, "Done processing #{$movie->imdbId}.");
            fclose($this->proc);
            $this->proc = null;
            $movie->status = Movie::STATUS_CACHED;
            $movie->progress = null;
        } else {
            $pcts = array_filter(explode(PHP_EOL, trim(fread($this->proc, 1024))));
            $movie->progress = end($pcts) > $movie->progress ? end($pcts) : $movie->progress;
            syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - processing {$movie->progress}%.");
        }

        $this->em->flush();
    }

    protected function updateDownloading(Movie $movie)
    {
        assert('$movie->status === \Duchesse\Chaton\Marie\Models\Movie::STATUS_DOWNLOADING');
        $torrent = $this->api->get($movie->torrentHash);
        $movie->progress = (int) $torrent->getPercentDone();
        $movie->eta = (int) $torrent->getEta();
        syslog(LOG_INFO, "{$movie->imdbId} ({$movie->title}) - downloading {$movie->progress}% (ETA {$movie->eta})");
        if ($torrent->isFinished()) {
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
        assert('$movie->status === \Duchesse\Chaton\Marie\Models\Movie::STATUS_NOT_CACHED');
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

<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\Models\Movie;

class Processor
{
    protected $em;
    protected $api;
    protected $imdbId;
    protected $videosDir;
    protected $sourcesDir;

    public function __construct($imdbId)
    {
        $this->em = Util::getEntityManager();
        $this->api = Util::getTransmissionApi();
        $this->imdbId = $imdbId;
        $this->videosDir  = dirname(__DIR__) . '/videos';
        $this->sourcesDir = $this->videosDir . '/sources';

        if (!file_exists($this->videosDir))
            mkdir($this->videosDir, 0755, true);
        if (!file_exists($this->sourcesDir))
            mkdir($this->sourcesDir, 0755, true);
    }

    public function __invoke($action, array $params = [])
    {
        syslog(LOG_INFO, "Start processing movie #{$this->imdbId}.");

        $movie = $this->em->getRepository('Marie:Movie')->find($this->imdbId);
        if ($movie === null)
            throw new \RuntimeException('Movie not found.');

        $realPath = $this->getMovieRealpath($movie);
        $sourcePath = realpath($this->sourcesDir) . '/' . $movie->torrentHash;
        if (file_exists($sourcePath))
            unlink($sourcePath);

        symlink($realPath, $sourcePath);

        $gif = new \GifTool(
            basename($sourcePath),
            '/opt/ffmpeg/ffprobe',
            $this->videosDir . '/',
            $this->sourcesDir . '/',
            '/opt/ffmpeg/ffmpeg',
            false
        );
        if($action === 'preview') {
            echo "50\n";
            $gif->to_mute();
            echo "100\n";
        } else if($action == 'gif') {
            $path = $gif->to_gif($params['start'], $params['stop'], $params['text'], $params['quality']);
            return substr($path, strlen($this->videosDir));
        }

        syslog(LOG_INFO, "Done processing movie #{$this->imdbId}.");
    }

    protected function getMovieRealpath(Movie $movie)
    {
        $torrent = $this->api->get($movie->torrentHash);
        if (!$torrent->isFinished())
            throw new \RuntimeException('Torrent not finished.');

        $biggest = $torrent->getFiles()[0];
        foreach ($torrent->getFiles() as $file) {
            if ($file->getSize() > $biggest->getSize())
                $biggest = $file;
        }
        assert('$biggest->getCompleted() === $biggest->getSize()');

        return realpath($this->api->getSession()->getDownloadDir() . '/' . $biggest->getName());
    }
}

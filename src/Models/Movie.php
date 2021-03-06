<?php
namespace Duchesse\Chaton\Marie\Models;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\ThePirateBay\Scraper;

/**
 * @Entity
 */
class Movie
{
    use \Duchesse\Chaton\Marie\Struct;

    /**
     * @Id @Column(length=9)
     */
    public $imdbId;

    /**
     * @Column
     */
    public $title;

    /**
     * @Column(type="integer", nullable=true)
     */
    public $eta;

    const STATUS_NOT_CACHED         = 'not_cached';
    const STATUS_DOWNLOADING        = 'downloading';
    const STATUS_PENDING_PROCESSING = 'pending_processing';
    const STATUS_PROCESSING         = 'processing';
    const STATUS_CACHED             = 'cached';

    /**
     * @Column(length=32)
     */
    public $status = self::STATUS_NOT_CACHED;

    /**
     * @Column(type="integer", nullable=true)
     */
    public $progress;

    /**
     * @Column(length=40)
     */
    public $torrentHash;

    /**
     * @Column(type="text")
     */
    public $magnet;

    public function setImdbId($imdbId)
    {
        $this->imdbId = $imdbId;
    }

    public function refreshFromImdb()
    {
        assert('strlen($this->imdbId)');
        $data = json_decode(file_get_contents(
            "http://www.omdbapi.com/?i={$this->imdbId}",
            true
        ));

        if (!is_object($data) || (property_exists($data, 'Response') && $data->Response === 'False'))
            throw new \RuntimeException("Unable to get IMDB data for `{$this->imdbId}`");

        $this->title = $data->Title;
    }

    public function refreshFromTpb()
    {
        $torrents = Scraper::search(
            $this->imdbId,
            Scraper::CAT_VIDEO,
            Scraper::ORDER_SEEDERS_DESC
        );
        if (count($torrents) !== 1)
            throw new \RuntimeException("Unable to find torrent for `{$this->imdbId}`.");

        $this->torrentHash = $torrents[0]->hash;
        $this->magnet      = $torrents[0]->magnet;
    }

    public function getStreamUrl()
    {
        if ($this->status !== self::STATUS_CACHED)
            return null;

        return Util::buildUrl(Util::strTpl(
            '{hash}/mute/mute-{hash}.mp4',
            ['hash' => $this->torrentHash]
        ));
    }

    public function getRealpath()
    {
        $api = Util::getTransmissionApi();
        $torrent = $api->get($this->torrentHash);
        if (!$torrent->isFinished())
            throw new \RuntimeException('Torrent not finished.');

        $biggest = $torrent->getFiles()[0];
        foreach ($torrent->getFiles() as $file) {
            if ($file->getSize() > $biggest->getSize())
                $biggest = $file;
        }
        assert('$biggest->getCompleted() === $biggest->getSize()');

        return realpath($api->getSession()->getDownloadDir() . '/' . $biggest->getName());
    }
}

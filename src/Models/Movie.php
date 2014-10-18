<?php
namespace Duchesse\Chaton\Marie\Models;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\ThePirateBay\Scraper;

/**
 * @Entity
 */
class Movie
{
    /**
     * @Id @Column(length=9)
     */
    public $imdbId;

    /**
     * @Column
     */
    public $title;

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
    public $downloadProgress;

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
}

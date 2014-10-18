<?php
namespace Duchesse\Chaton\Marie\Models;

use Duchesse\Chaton\Marie\Util;

/**
 * @Entity
 */
class Movie
{
    /**
     * @Id @Column(length=9)
     */
    protected $imdbId;

    /**
     * @Column
     */
    protected $title;

    const STATUS_NOT_CACHED  = 'not_cached';
    const STATUS_DOWNLOADING = 'downloading';
    const STATUS_PROCESSING  = 'processing';
    const STATUS_CACHED      = 'cached';

    /**
     * @Column(length=16)
     */
    protected $status = self::STATUS_NOT_CACHED;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $downloadProgress;

    /**
     * @Column(length=40)
     */
    protected $torrentHash;
}

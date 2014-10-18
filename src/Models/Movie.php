<?php

namespace Duchesse\Chaton\Marie\Models;

/**
 * @Entity
 */
class Movie
{
    /**
     * @Id @Column
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
     * @Column
     */
    protected $status = self::STATUS_NOT_CACHED;
}

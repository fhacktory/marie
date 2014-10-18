<?php

namespace Duchesse\Chaton\Marie;

use Duchesse\Chaton\Marie\Util;
use Duchesse\Chaton\Marie\Models\Movie;

class Processor
{
    protected $em;
    protected $imdbId;

    public function __construct($imdbId)
    {
        $this->em = Util::getEntityManager();
        $this->imdbId = $imdbId;
    }

    public function __invoke()
    {
        syslog(LOG_INFO, "Starting processing movie #{$this->imdbId}.");

        for($i = 0; $i <= 100; $i += 10) {
            echo $i . PHP_EOL;
            sleep(1);
        }

        syslog(LOG_INFO, "Done processing movie #{$this->imdbId}.");
    }
}

<?php

namespace Duchesse\Chaton\Marie;

class TransmissionDaemon
{
    public function __invoke()
    {
        syslog(LOG_INFO, 'Starting TransmissionDaemon.');
        for(;;) {
            sleep(1);
        }
    }
}

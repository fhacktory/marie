<?php
/// Configuration file for Doctrine.

namespace Duchesse\Chaton\Marie;

use Doctrine\ORM\Tools\Console\ConsoleRunner;

error_reporting(-1);
require_once 'vendor/autoload.php';

return ConsoleRunner::createHelperSet(Util::getEntityManager());

<?php

namespace Duchesse\Chaton\Marie;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Util
{
    /**
     * Replace {key} in $tpl with the corresponding value in $values.
     *
     * @param string $tpl
     * @param string[] $values
     */
    public static function strTpl($tpl, $values)
    {
        $keys = array_map(function($k) {
            return sprintf('{%s}', $k);
        }, array_keys($values));
        return str_replace($keys, $values, $tpl);
    }

    /**
     * @return Doctrine\ORM\EntityManager;
     */
    public static function getEntityManager()
    {
        static $em = null;
        if($em === null) {
            $dbConfig = [
                'driver'   => 'pdo_mysql',
                'user'     => 'marie',
                'password' => 'callas',
                'dbname'   => 'marie',
            ];
            $meta = Setup::createAnnotationMetadataConfiguration(
                ['src/Models'],
                true
            );
            $meta->addEntityNamespace('Marie', 'Duchesse\Chaton\Marie\Models');
            $em = EntityManager::create($dbConfig, $meta);
        }

        return $em;
    }
}

<?php

namespace Duchesse\Chaton\Marie;

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
}

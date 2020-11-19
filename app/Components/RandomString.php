<?php

namespace App\Components;

class RandomString
{
    /**
     * @param int $length
     * @return string
     */
    public static function generate($length = 20)
    {
        return str_random($length);
    }

    /**
     * @param string $str_prefix
     *
     * @return string
     */
    public static function generateWithPrefix($str_prefix)
    {
        return ($str_prefix ."_". str_random(4) .'-'. str_random(8) .'-'. str_random(12));
    }
}
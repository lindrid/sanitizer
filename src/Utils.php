<?php

declare(strict_types=1);

namespace Lindrid\Sanitizer;

class Utils
{
    /**
     * @param mixed $var
     * @return bool
     */
    public static function isSequentialArray($var): bool
    {
        if ($var === []) return true;
        return is_array($var) && array_keys($var) === range(0, count($var) - 1);
    }

    /**
     * @param mixed $var
     * @return bool
     */
    public static function isAssociativeArray($var): bool
    {
        if ($var === []) return true;
        return is_array($var) && array_keys($var) !== range(0, count($var) - 1);
       /* if (is_array($var)) {
            foreach (array_keys($var) as $key) {
                if (!is_string($key)) {
                    return false;
                }
            }
        }*/
        return true;
    }

    /**
     * @param array $a1
     * @param array $a2
     * @return bool
     */
    public static function keysAreEqual(array $a1, array $a2): bool
    {
        return !array_diff_key($a1, $a2) && !array_diff_key($a2, $a1);
    }

    public static function isPhone($value)
    {
        return false;
    }
}

<?php

namespace Flow;

final class Helper
{
    public static function abs($obj = null)
    {
        return abs(intval($obj));
    }

    public static function bytes($obj = null, $decimals = 1, $dec = '.', $sep = ',')
    {
        $obj = max(0, intval($obj));
        $places = strlen($obj);
        if ($places <= 9 && $places >= 7) {
            $obj = number_format($obj / 1048576, $decimals, $dec, $sep);
            return "$obj MB";
        } elseif ($places >= 10) {
            $obj = number_format($obj / 1073741824, $decimals, $dec, $sep);
            return "$obj GB";
        } elseif ($places >= 4) {
            $obj = number_format($obj / 1024, $decimals, $dec, $sep);
            return "$obj KB";
        } else {
          return "$obj";
        }
    }

    public static function capitalize($obj)
    {
        return ucfirst(strval($obj));
    }

    public static function cycle($obj = null)
    {
        $obj = ($obj instanceof \Traversable) ?
            iterator_to_array($obj) : (array) $obj;
        return new Helper\Cycler((array) $obj);
    }

    public static function date($obj = null, $format = 'Y-m-d')
    {
        return date($format, $obj ?: time());
    }

    public static function dump($obj = null)
    {
        return var_export($obj, true);
    }

    public static function e($obj = null, $force = false)
    {
        return self::escape($obj, $force);
    }

    public static function escape($obj = null, $force = false)
    {
        return htmlspecialchars(strval($obj), ENT_QUOTES, 'UTF-8', $force);
    }

    public static function first($obj = null, $default = null)
    {
        if (is_string($obj)) {
            return strlen($obj) ? substr($obj, 0, 1) : $default;
        }
        $obj = ($obj instanceof \Traversable) ?
            iterator_to_array($obj) : (array) $obj;
        $keys = array_keys($obj);
        if (count($keys)) {
            return $obj[$keys[0]];
        }
        return $default;
    }

    public static function format($obj, $args)
    {
        return call_user_func_array('sprintf', func_get_args());
    }

    public static function is_iterable($obj = null)
    {
        return is_array($obj) || ($obj instanceof \Traversable);
    }

    public static function is_divisible_by($obj = null, $number = null)
    {
        if (!isset($number)) return false;
        if (!is_numeric($obj) || !is_numeric($number)) return false;
        if ($number == 0) return false;
        return fmod($obj, $number) == 0;
    }

    public static function is_empty($obj = null)
    {
        if (is_null($obj)) {
            return true;
        } elseif (is_array($obj)) {
            return empty($obj);
        } elseif (is_string($obj)) {
            return strlen($obj) == 0;
        } elseif ($obj instanceof \Countable) {
            return count($obj) ? false : true;
        } elseif ($obj instanceof \Traversable) {
            return iterator_count($obj);
        } else {
            return false;
        }
    }

    public static function is_even($obj = null)
    {
        if (is_scalar($obj) || is_null($obj)) {
            $obj = is_numeric($obj) ? intval($obj) : strlen($obj);
        } elseif (is_array($obj)) {
            $obj = count($obj);
        } elseif ($obj instanceof \Traversable) {
            $obj = iterator_count($obj);
        } else {
            return false;
        }
        return abs($obj % 2) == 0;
    }

    public static function is_odd($obj = null)
    {
        if (is_scalar($obj) || is_null($obj)) {
            $obj = is_numeric($obj) ? intval($obj) : strlen($obj);
        } elseif (is_array($obj)) {
            $obj = count($obj);
        } elseif ($obj instanceof \Traversable) {
            $obj = iterator_count($obj);
        } else {
            return false;
        }
        return abs($obj % 2) == 1;
    }

    public static function join($obj = null, $glue = '')
    {
        $obj = ($obj instanceof \Traversable) ?
            iterator_to_array($obj) : (array) $obj;
        return join($glue, $obj);
    }

    public static function json_encode($obj = null)
    {
        return json_encode($obj);
    }

    public static function keys($obj = null)
    {
        if (is_array($obj)) {
            return array_keys($obj);
        } elseif ($obj instanceof \Traversable) {
            return array_keys(iterator_to_array($obj));
        }
        return null;
    }

    public static function last($obj = null, $default = null)
    {
        if (is_string($obj)) {
            return strlen($obj) ? substr($obj, -1) : $default;
        }
        $obj = ($obj instanceof \Traversable) ?
            iterator_to_array($obj) : (array) $obj;
        $keys = array_keys($obj);
        if ($len = count($keys)) {
            return $obj[$keys[$len - 1]];
        }
        return $default;
    }

    public static function length($obj = null)
    {
        if (is_string($obj)) {
            return strlen($obj);
        } elseif (is_array($obj) || ($obj instanceof \Countable)) {
            return count($obj);
        } elseif ($obj instanceof \Traversable) {
            return iterator_count($obj);
        } else {
            return 1;
        }
    }

    public static function lower($obj = null)
    {
        return strtolower(strval($obj));
    }

    public static function nl2br($obj = null, $is_xhtml = false)
    {
        return nl2br(strval($obj), $is_xhtml);
    }

    public static function number_format($obj = null, $decimals = 0,
        $dec_point = '.', $thousands_sep = ',')
    {
        return number_format(strval($obj), $decimals, $dec_point, $thousands_sep);
    }

    public static function range($lower = null, $upper = null, $step = 1)
    {
        return new Helper\RangeIterator(intval($lower), intval($upper), intval($step));
    }

    public static function repeat($obj, $times = 2)
    {
        return str_repeat(strval($obj), $times);
    }

    public static function replace($obj = null, $search = '', $replace = '',
        $regex = false)
    {
        if ($regex) {
            return preg_replace($search, $replace, strval($obj));
        } else {
            return str_replace($search, $replace, strval($obj));
        }
    }

    public static function strip_tags($obj = null, $allowableTags = '')
    {
        return strip_tags(strval($obj), $allowableTags);
    }

    public static function title($obj = null)
    {
        return ucwords(strval($obj));
    }

    public static function trim($obj = null, $charlist = " \t\n\r\0\x0B")
    {
        return trim(strval($obj), $charlist);
    }

    public static function truncate($obj = null, $length = 255,
        $preserve_words = false, $hellip = '&hellip;')
    {
        $obj = strval($obj);

        $len = strlen($obj);

        if ($length >= $len) return $obj;

        $truncated = $preserve_words ?
            preg_replace('/\s+?(\S+)?$/', '', substr($obj, 0, $length + 1)) :
            substr($obj, 0, $length);

        return $truncated . $hellip;
    }

    public static function unescape($obj = null)
    {
        return htmlspecialchars_decode(strval($obj), ENT_QUOTES);
    }

    public static function upper($obj = null)
    {
        return strtoupper(strval($obj));
    }

    public static function url_encode($obj = null)
    {
        return urlencode(strval($obj));
    }

    public static function word_wrap($obj = null, $width = 75, $break = "\n",
        $cut = false)
    {
        return wordwrap(strval($obj), $width, $break, $cut);
    }
}


<?php

/**
 * AtomMVC: Globals Class
 * atom/app/lib/Globals.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Globals {

    public static function value(&$global, $key = null, $value = null) {

        if(isset($global) && is_array($global)) {

            if(is_null($key) && is_null($value)) {

                return $global;

            } else if(is_null($value) && array_key_exists($key, $global)) {

                return $global[$key];

            } else {

                $global[$key] = $value;
            }
        }
    }

    public static function hasKey(&$global, $key){

        $hasKey = false;

        if(isset($global) && is_array($global)) {

            $hasKey = array_key_exists($key, $global) ? true : false;
        }

        return $hasKey;
    }

    public static function hasValue(&$global, $key){

        $hasValue = false;

        if(static::hasKey($global, $key)) {
            $hasValue = isset($global[$key]) ? true : false;
        }

        return $hasValue;
    }

    public static function clear(&$global, $key) {

        if(static::hasKey($global, $key)) {
            unset($global[$key]);
        }
    }

    public static function clearAll(&$global) {

        $global = array();
    }
}
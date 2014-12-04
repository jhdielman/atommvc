<?php

/**
 * AtomMVC: Input Class
 * atom/app/lib/Input.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Input {

    public static function get($key = null, $value = null) {
        return Globals::value($_GET, $key, $value);
    }

    public static function hasGet($key) {
        return Globals::hasValue($_GET, $key);
    }

    public static function clearGet($key) {
        Globals::clear($_GET, $key);
    }

    public static function post($key = null, $value = null) {
        return Globals::value($_POST, $key, $value);
    }
    
    public static function any($key = null, $value = null) {
        if (static::hasGet($key)) {
            return static::get($key, $value);
        }
        else if (static::hasPost($key)) {
            return static::post($key, $value);
        }
        
        return null;
    }

    public static function hasAny($key) {
        return (static::hasGet($key) || static::hasPost($key));
    }

    public static function hasPost($key) {
        return Globals::hasValue($_POST, $key);
    }

    public static function clearPost($key) {
        Globals::clear($_POST, $key);
    }

    public static function files($key = null, $value = null) {
        return Globals::value($_FILES, $key, $value);
    }

    public static function hasFiles($key) {
        return Globals::hasValue($_FILES, $key);
    }

    public static function clearFiles($key) {
        Globals::clear($_FILES, $key);
    }
}

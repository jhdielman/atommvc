<?php

/**
 * AtomMVC: Config Class
 * atom/app/lib/Config.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Config {


    protected static $exclude = array('.','..','constants');
    protected static $configs;

    public static function load($files = array()) {

        $configFiles = count($files) ? $files : scandir(ATOM_CONFIG_PATH);

        foreach($configFiles as $file) {
            $file = rtrim($file, PHPEXT);
            if(static::isValidConfig($file)) {
                static::$configs[$file] = require ATOM_CONFIG_PATH.$file.PHPEXT;
            }
        }
    }

    public static function get($config, $key) {

        $value = '';

        if(array_key_exists($config, static::$configs)) {

            $cfg = static::$configs[$config];

            if(array_key_exists($key, $cfg)) {
                $value = $cfg[$key];
            }
        }

        return $value;
    }

    private static function isValidConfig($file) {
        $isIncluded = !in_array($file, static::$exclude);
        $isConfigFile = is_file(ATOM_CONFIG_PATH.$file.PHPEXT);
        return $isIncluded && $isConfigFile;
    }
}

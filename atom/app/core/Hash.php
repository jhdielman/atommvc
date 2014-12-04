<?php

/**
 * AtomMVC: Hash Class
 * atom/app/lib/Hash.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 */

namespace Atom;

class Hash {

    protected static $algo = PASSWORD_BCRYPT;
    protected static $cost = 12;

    public static function create($password) {

        return password_hash($password, static::$algo, ['cost' => static::$cost]);
    }

    public static function verify($password, $hash) {

        return password_verify($password, $hash);
    }

    public static function needsRehash($hash) {

        return password_needs_rehash($hash, static::$algo, ['cost' => static::$cost]);
    }

    public static function sha512($data, $rawOutput = false) {
        return hash('sha512', $data,$rawOutput);
    }

    protected static function getPasswordHashCost() {

        $timeTarget = 0.4;

        $cost = 9;
        do {
            $cost++;
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ["cost" => $cost]);
            $end = microtime(true);
        } while (($end - $start) < $timeTarget);
        return $cost;
    }
}
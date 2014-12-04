<?php

/**
 * AtomMVC: Cookie Class
 * atom/app/lib/Cookie.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Cookie {

    protected static $path = '/';
    protected static $domain = '';
    protected static $secure = true;

    public static function value($key = null) {
        return Globals::value($_COOKIE, $key);
    }

    public static function hasValue($key) {
        return Globals::hasValue($_COOKIE, $key);
    }

    public static function set($name, $value, $minutes = 0, $path = null, $domain = null, $secure = true, $httpOnly = true) {

        $path = $path ?: Config::get('master', 'cookiePath') ?: static::$path;

        $domain = $domain ?: Config::get('master', 'cookieDomain') ?: static::$domain;

        $secure = $secure ?: Config::get('master','cookieSecure') ?: static::$secure;

        $secure = $secure && Request::isSecure();

        $expire = ($minutes == 0) ? 0 : time() + ($minutes * 60);
        
        //if($name == Security::getAntiCsrfCookieName()) {
        //    $num = Session::value('cookieSets');
        //    if(!$num) {
        //        $num = 1;
        //    } else {
        //        $num++;
        //    }
        //    Session::value('cookie'."{$num}", $value);
        //    Session::value('cookieSets', $num);
        //}

        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    public static function clear($name, $path = null, $domain = null) {
        return static::set($name, null, -2628000, $path, $domain);
    }
}

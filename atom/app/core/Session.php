<?php

/*
 * AtomMVC: Session Class
 * atom/app/lib/Session.php
 *
 * @copyright Original Copyright (c) 2009, Robert Hafner
 * All rights reserved.
 *
 * @author Jason Dielman
 * This is a direct implementation of Robert's Session class. This implementation
 * will regenerate a new session_id on each request.
 *
 */

namespace Atom;

class Session {

    protected static $aolProxies = array('195.93.', '205.188', '198.81.', '207.200', '202.67.', '64.12.9');

    public static function value($key = null, $value = null) {
        return Globals::value($_SESSION, $key, $value);
    }

    public static function hasKey($key){
        return Globals::hasKey($_SESSION, $key);
    }

    public static function hasValue($key){
        return Globals::hasValue($_SESSION, $key);
    }

    public static function clear($key) {
        return Globals::clear($_SESSION, $key);
    }

    public static function clearAll() {
        return Globals::clearAll($_SESSION);
    }

    public static function start($name, $limit = 0, $path = '/', $domain = null, $secure = null) {

        // Set the cookie name
        session_name($name . '_Session');

        // Set SSL level
        $https = isset($secure) ? $secure : Request::isSecure();

        // Set session cookie options
        session_set_cookie_params($limit, $path, $domain, $https, true);
        session_start();

        // Let's go ahead and regenerate the id each request
        static::regen();

        // Make sure the session hasn't expired, and destroy it if it has
        if(static::isValid()) {

            // Check to see if the session is new or a hijacking attempt
            if(!static::preventHijacking()) {

                // Reset session data and regenerate id
                //$_SESSION = array();
                //$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
                //$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
                static::clearAll();
                static::value('IPaddress', Request::server('REMOTE_ADDR'));
                static::value('userAgent', Request::server('HTTP_USER_AGENT'));
                static::regen();

            // Give a 5% chance of the session id changing on any request
            } elseif(rand(1, 100) <= 5) {
                static::regen();
            }
        }else{
            static::clearAll();
            session_destroy();
            session_start();
        }
    }

    public static function regen() {

        // If this session is obsolete it means there already is a new id
        if(static::hasValue('OBSOLETE') && static::value('OBSOLETE') === true) {
            return;
        }

        // Set current session to expire in 10 seconds
        static::value('OBSOLETE', true);
        static::value('EXPIRES',(time() + 10));

        // Create new session without destroying the old one
        session_regenerate_id(false);

        // Grab current session ID and close both sessions to allow other scripts to use them
        $newSession = session_id();
        session_write_close();

        // Set session ID to the new one, and start it back up again
        session_id($newSession);
        session_start();

        // Now we unset the obsolete and expiration values for the session we want to keep
        static::clear('OBSOLETE');
        static::clear('EXPIRES');
    }

    protected static function isValid() {

        if(static::hasValue('OBSOLETE') && !static::hasValue('EXPIRES') ) {
            return false;
        }

        if(static::hasValue('EXPIRES') && static::value('EXPIRES') < time()) {
            return false;
        }

        return true;
    }

    protected static function preventHijacking() {

        if(!static::hasValue('IPaddress') || !static::hasValue('userAgent')) {
            return false;
        }

        if(static::value('userAgent') != Request::server('HTTP_USER_AGENT')
           && !(strpos(static::value('userAgent'), 'Trident') !== false
                && strpos(Request::server('HTTP_USER_AGENT'), 'Trident') !== false)) {
            return false;
        }

        $ipHeader = substr(static::value('IPaddress'), 0, 7);
        $remoteIpHeader = substr(Request::server('REMOTE_ADDR'), 0, 7);

        if(static::value('IPaddress') != Request::server('REMOTE_ADDR')
            && !(in_array($ipHeader, static::$aolProxies)
                 && in_array($remoteIpHeader, static::$aolProxies))) {
            return false;
        }

        if(static::value('userAgent') != Request::server('HTTP_USER_AGENT')) {
            return false;
        }

        return true;
    }
}
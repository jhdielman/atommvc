<?php

/**
 * AtomMVC: Request Class
 * atom/app/lib/Request.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Request {
    public static $headers = [];
    public static $segments = [];
    public static $path = '';
    public static $queryString = '';
    public static $fragment = '';
    protected static $format;

    protected static $formats = [
        'html' => ['text/html',
                   'application/xhtml+xml'],
        'txt'  => ['text/plain'],
        'js'   => ['application/javascript',
                   'application/x-javascript',
                   'text/javascript'],
        'css'  => ['text/css'],
        'json' => ['application/json',
                   'application/x-json'],
        'xml'  => ['text/xml',
                   'application/xml',
                   'application/x-xml'],
        'rdf'  => ['application/rdf+xml'],
        'atom' => ['application/atom+xml'],
        'rss'  => ['application/rss+xml']
    ];

    public static function parse() {
        static::parseUri();
        static::parseHeaders();
    }

    public static function getUri() {
        return static::server('REQUEST_URI');
    }

    public static function getQueryString() {
        return static::$queryString;
    }
    
    public static function getPath() {
        return static::$path;
    }

    public static function getPathInfo($key = null) {
        return static::server('PATH_INFO');
    }
    
    public static function getSegments() {
        return new Collection(static::$segments);
    }

    public static function segment($index) {
        $segment = static::getSegments()->get($index);
        return trim($segment,'/');
    }

    public static function cookie($key = null, $value = null) {
        return Globals::value($_COOKIE, $key, $value);
    }

    public static function server($key = null, $value = null) {
        return Globals::value($_SERVER, $key, $value);
    }

    public static function method() {
        return static::server('REQUEST_METHOD');
    }

    public static function isPost() {
        return static::methodIs('POST');
    }

    public static function isGet() {
        return static::methodIs('GET');
    }

    public static function isApiRequest() {
        return strtolower(static::segment(0)) == 'api';
    }

    public static function isAjax() {

        $isAjax = false;
        $xrw = strtolower(static::header('X-Requested-With'));

        if($xrw === 'xmlhttprequest') {
            $isAjax = true;
        }

        return $isAjax;
    }
    
    public static function isSafe() {
        $safeMethods = ['GET','HEAD','OPTIONS','TRACE'];
        $method = strtoupper(static::method());
        return in_array($method,$safeMethods);
    }
    
    public static function hasToken() {
        return static::hasHeader('X-CSRFToken');
    }
    
    public static function getToken() {
        $token = null;
        $key = 'X-CSRFToken';
        if(static::hasHeader($key)) {
            $token = static::header($key);
        }
        return $token;
    }

    public static function header($key) {

        $header = null;

        if(isset(static::$headers[$key])) {
            $header = static::$headers[$key];
        }

        return $header;
    }
    
    public static function hasHeader($key) {
        return static::header($key) != null;
    }

    protected static function parseHeaders() {

        if (!function_exists('getallheaders')) {
            foreach (static::server() as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerKey = (new String($name))
                        ->subStr(5)
                        ->replace('_', ' ')
                        ->toLower()
                        ->upperCaseWords()
                        ->replace(' ','-')
                        ->out();
                    static::$headers[$headerKey] = $value;
                }
            }
        } else {
            static::$headers = getallheaders();
        }
    }

    protected static function parseUri() {

        $requestUri = static::getUri();

        if(!empty($requestUri)) {
            // Parse the fragment
            $uriComponents = explode('#', $requestUri);
            $uriComponentCount = count($uriComponents);
            
            if($uriComponentCount) {
                $requestUri = $uriComponents[0];
            }
            
            if($uriComponentCount > 1) {
                static::$fragment = $uriComponents[1];
            }
            
            // Parse the path and query string
            $uriComponents = explode('?', $requestUri);
            $uriComponentCount = count($uriComponents);
            
            if($uriComponentCount) {
                static::$path = $uriComponents[0];
                static::$segments = explode('/', trim($uriComponents[0],'/'));
            }
            
            if($uriComponentCount > 1) {
                static::$queryString = $uriComponents[1];
            }
        }
    }

    public static function isSecure() {
        $httpsSet = !empty(static::server('HTTPS'));
        $sslOn = strtolower(static::server('HTTPS')) !== 'off';
        return $httpsSet && $sslOn;
    }

    public static function isFromCli() {
        return (php_sapi_name() === 'cli' OR defined('STDIN'));
    }

    public static function methodIs($method) {
        return strtoupper(static::method()) === strtoupper($method);
    }

    public static function getRequestFormat() {

        $contentType = static::header('Content-Type');

        if (static::$format === null && $contentType !== null) {

            $contentType = strtolower($contentType);

            foreach(static::$formats as $format => $mimeTypes) {
                if(in_array($contentType, $mimeTypes)) {
                    static::$format = $format;
                }
            }
        }

        return static::$format;
    }

    public function getMimeType($format) {

        return isset(static::$formats[$format]) ? static::$formats[$format][0] : null;
    }
}
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
    
	public static $segments = array();
    public static $queryString = '';
	public static $headers = '';
    
    public static function parse() {
		static::parseUri();
		static::parseHeaders();
	}

	public static function getQueryString() {
		return static::$queryString;
	}

	public static function getSegments() {
		return new Collection(static::$segments);
	}

	public static function segment($index) {
        $segment = static::getSegments()->value($index);
        return trim($segment,'/');
	}
    
    public static function cookie($key = null, $value = null) {
        return Globals::value($_COOKIE, $key, $value);
    }

	public static function server($key = null, $value = null) {
        return Globals::value($_SERVER, $key, $value);
	}
    
    public static function redirect($url, $statusCode = 303) {
        
        if(is_numeric($url)) {
            
            $code = (int) $url;
            $message = Config::get('status', $code);
            
            if(!$message) {
                $message = 'The request failed';
            }
            
            if($code >= 400) {
                Error::show($message, $code);
            } else { 
                include ATOM_ERROR_PATH.'error'.PHPEXT;
            }
            
        } else {
            header('Location: ' . $url, true, $statusCode);
        }
        exit();
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
		$xrw = strtolower(static::getHeader('X-Requested-With'));
		
		if($xrw === 'xmlhttprequest') {
			$isAjax = true;
		}
		
		return $isAjax;
	}
	
	public static function getHeader($key) {
		
		$header = '';
		
		if(isset(static::$headers[$key])) {
			$header = static::$headers[$key];
		}
		
		return $header;
	}
	
	public static function setHeader($key, $value, $replace = true, int $http_response_code = null) {
		
		if(is_null($http_response_code)) {
			header("$key: $value", $replace);
		} else {
			header("$key: $value", $replace, $http_response_code);			
		}
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
		
		$requestUri = trim(static::server('REQUEST_URI'),'/');

		if(!empty($requestUri)) {

			$uriComponents = explode('?', $requestUri);
			$uriComponentCount = count($uriComponents);

			if($uriComponentCount) {
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
	
	protected static function methodIs($method) {
		return strtoupper(static::method()) === strtoupper($method);
	}
}
<?php

/**
 * AtomMVC: Router Class
 * atom/app/lib/Router.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

use ReflectionMethod;

class Router {
	
	public static $controllerSegmentIndex;
	
	public static $methodSegmentIndex;
	
	public static $argsSegmentStartIndex;
	
	public static function route() {
				
		Request::parse();
		
		// Note: You might want to add your domain and path
		Session::start();

		if(Security::antiCsrfVerified() && Auth::accessGranted()) {
			
			$namespace = __NAMESPACE__;
			$controller = static::getController();
			$method = static::getControllerMethod();
			$args = static::getControllerMethodArgs();
			
			if(static::isValidRoute($namespace, $controller, $method, $args)) {

				$controller = "$namespace\\$controller";
				$controller = new $controller();
				call_user_func_array([$controller, $method], $args);
			} else {
				Request::redirect(404);
			}
		} else {
			Error::show('Quit hacking!!!');
		}
		
		exit();
	}
    
    protected static function isValidRoute($namespace, $controller, $method, $args) {
        
        $validRoute = false;
        $controllerFile = static::getControllerPath().$controller.PHPEXT;
		
        if(is_file($controllerFile)) {
           
            require $controllerFile;
			
			$controller = "$namespace\\$controller";
									
            if(method_exists($controller, $method)) {
				
                $validRoute = static::isValidMethod($controller, $method, $args);
            }
        }
        
        return $validRoute;
    }
	
	protected static function isValidMethod($controller, $method, $args) {
		
		$argCount = count($args);
		$controllerMethod = new ReflectionMethod($controller, $method);
		$isPublic = $controllerMethod->isPublic();
		$paramCount = $controllerMethod->getNumberOfParameters();
		$requiredParamCount = $controllerMethod->getNumberOfRequiredParameters();
		$validParams = ($argCount <= $paramCount) && ($argCount >= $requiredParamCount);

		return (bool) ($isPublic && $validParams);
	}
	
	protected static function getControllerPath() {
		
		if(Request::isApiRequest()) {
			return ATOM_API_PATH;
		} else {
			return ATOM_CONTROLLER_PATH;
		}
	}

	protected static function getController() {
		
		$index = static::getSegmentIndex(0);
		
		return static::segmentToCamelCase($index,'home').'Controller';
	}

	protected static function getControllerMethod() {
		
		$index = static::getSegmentIndex(1);

		return static::segmentToCamelCase($index,'index', false);
	}
    
    protected static function getControllerMethodArgs() {
		
		$index = static::getSegmentIndex(2);
		
        return Request::getSegments()->range($index)->out();
	}
	
	protected static function getSegmentIndex($index) {
		
		$segmentIndex = $index;
		
		if(Request::isApiRequest()) {
			$segmentIndex++;
		}
		
		return $segmentIndex;
	}

	protected static function segmentToCamelCase($index, $default, $capitalizeFirst = true, $delimiter = '-') {

		$segment = Request::segment($index);
		$camelCaseSegment = $default;
		
		if (!empty($segment)) {
			$camelCaseSegment = (new String($segment))->toCamelCase($capitalizeFirst, $delimiter)->out();
		}

		return $camelCaseSegment;
	}
}
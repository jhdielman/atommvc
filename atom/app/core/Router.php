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
    
    protected static $controllerSegmentIndex = null;
    
    protected static $controllerSegment = null;
    
    protected static $controllerName = null;
    
    protected static $controller = null;
    
    protected static $controllerMethodSegmentIndex = null;
    
    protected static $controllerMethodSegment = null;
        
    protected static $controllerMethod = null;
    
    protected static $contollerMethodArgsIndex = null;
    
    protected static $controllerMethodArgs = null;
        
    public static function route() {

        Request::parse();

        Session::start('aerobazaar',0,'/');

        if(Auth::accessGranted()) {
            $valid = false;
            $namespace = __NAMESPACE__;
            $controller = static::getController();
            $method = static::getControllerMethod();
            $prefixedMethod = strtolower(Request::method()).ucfirst($method);
            $controllerMethodArgs = static::getControllerMethodArgs();

            if(static::isValidController($namespace, $controller)) {
                $controller = (Request::isApiRequest() ? "$namespace\\Api\\$controller" : "$namespace\\$controller");

                // Check the method prefixed with the HTTP method and give it priority
                // otherwise check the non-prefixed method
                if (static::isValidMethod($controller, $prefixedMethod, $controllerMethodArgs)) {
                    $valid = true;
                    $method = $prefixedMethod;
                } else if(static::isValidMethod($controller, $method, $controllerMethodArgs)) {
                    $valid = true;
                }
            }

            if($valid === true){
                if(Security::antiCsrfVerified()) {
                    $controller = new $controller();
                    $renderable = call_user_func_array([$controller, $method], $controllerMethodArgs);
                    if($renderable instanceof IRenderable) {
                        $renderable->render();
                    }
                } else {
                    Error::show('CSRF Validation Failed: Forbidden access', 403);
                }
            } else {
                Response::redirect(404);
            }
        } else {
            Error::show('You are not authorized to see this content', 401);
        }
        exit();
    }

    protected static function isValidController($namespace, $controller) {

        $validController = false;
        $controllerFile = static::getControllerPath().$controller.PHPEXT;

        if($validController = is_file($controllerFile)) {
            require $controllerFile;
        }

        return $validController;
    }

    protected static function isValidMethod($controller, $method, $controllerMethodArgs) {

        $validMethod = false;

        if(method_exists($controller, $method)) {
            $argCount = count($controllerMethodArgs);
            $controllerMethod = new ReflectionMethod($controller, $method);
            $isPublic = $controllerMethod->isPublic();
            $paramCount = $controllerMethod->getNumberOfParameters();
            $requiredParamCount = $controllerMethod->getNumberOfRequiredParameters();
            $validParams = ($argCount <= $paramCount) && ($argCount >= $requiredParamCount);
            $validMethod = (bool) ($isPublic && $validParams);
        }

        return $validMethod;
    }

    protected static function getControllerPath() {

        if(Request::isApiRequest()) {
            return ATOM_API_PATH;
        } else {
            return ATOM_CONTROLLER_PATH;
        }
    }
    
    public static function getControllerSegmentIndex() {
        if(!static::$controllerSegmentIndex) {
            static::$controllerSegmentIndex = static::getSegmentIndex(0);
        }
        return static::$controllerSegmentIndex;
    }
    
    public static function getControllerSegment() {
        if(!static::$controllerSegment) {
            $index = static::getControllerSegmentIndex();
            static::$controllerSegment = Request::segment($index) ?: 'home';
        }
        return static::$controllerSegment;
    }
    
    public static function getControllerName() {
        if(!static::$controllerName) {
            $segment = static::getControllerSegment();
            static::$controllerName = static::segmentToCamelCase($segment, 'home');
        }
        return static::$controllerName;
    }
    
    public static function getController() {
        if(!static::$controller) {
            $name = static::getControllerName();
            static::$controller = $name . 'Controller';
        }
        return static::$controller;
    }
    
    public static function getControllerMethodSegmentIndex() {
        if(!static::$controllerMethodSegmentIndex) {
            static::$controllerMethodSegmentIndex = static::getSegmentIndex(1);
        }
        return static::$controllerMethodSegmentIndex;
    }
    
    public static function getControllerMethodSegment() {
        if(!static::$controllerMethodSegment) {
            $index = static::getControllerMethodSegmentIndex();
            static::$controllerMethodSegment = Request::segment($index) ?: 'index';
        }
        return static::$controllerMethodSegment;
    }
    
    public static function getControllerMethod() {
        if(!static::$controllerMethod) {
            $segment = static::getControllerMethodSegment();
            static::$controllerMethod = static::segmentToCamelCase($segment,'index', false);
        }
        return static::$controllerMethod;
    }
    
    public static function getControllerMethodArgsIndex() {
        if(!static::$contollerMethodArgsIndex) {
            static::$contollerMethodArgsIndex = static::getSegmentIndex(2);
        }
        return static::$contollerMethodArgsIndex;
    }

    public static function getControllerMethodArgs() {
        if(!static::$controllerMethodArgs) {
            $index = static::getControllerMethodArgsIndex();
            static::$controllerMethodArgs = Request::getSegments()->range($index)->out();
        }
        return static::$controllerMethodArgs;
    }
    
    public static function getRoutePath($includeLeadingSlash = true, $includeArgs = false, $separator = '/') {
        $prefix = ($includeLeadingSlash && $separator == '/') ? $separator : '';
        $components = [
            static::getControllerSegment(),
            static::getControllerMethodSegment()
        ];
        
        if($includeArgs === true) {
            $components = array_merge($components, static::getControllerMethodArgs());
        }
        
        $routePath = strtolower($prefix.implode($separator,$components));
        
        return $routePath;
    }

    protected static function getSegmentIndex($index) {

        $segmentIndex = $index;

        if(Request::isApiRequest()) {
            $segmentIndex++;
        }

        return $segmentIndex;
    }

    protected static function segmentToCamelCase($segment, $default, $capitalizeFirst = true, $delimiter = '-') {
        
        if(is_int($segment)) {
            $segment = Request::segment($segment);
        }
        
        $segment = $segment ?: $default;
        
        $camelCaseSegment = (new String($segment))
            ->toCamelCase($capitalizeFirst, $delimiter)
            ->out();

        return $camelCaseSegment;
    }
}
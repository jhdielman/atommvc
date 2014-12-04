<?php

namespace Atom;

abstract class Controller {

    public function __construct() {
    }

    protected function view($name = null, $model = null, $template = null) {
        return new View($name, $model, $template);
    }

    protected function json($model) {
        return new Json($model);
    }
    
    protected function redirect($url, $statusCode = Response::HTTP_FOUND) {
        Response::redirect($url, $statusCode);
    }
}
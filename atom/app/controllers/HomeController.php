<?php

namespace Atom;

class HomeController extends Controller {
    public function index() {
        return $this->view('index');
    }
}
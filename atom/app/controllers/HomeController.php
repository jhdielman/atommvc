<?php

namespace Atom;

class HomeController extends Controller {

	public function index() {
				
		View::render('index');
	}
}
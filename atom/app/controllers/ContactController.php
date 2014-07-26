<?php

namespace Atom;

class ContactController extends Controller {
	
	public function index() {
				
		$model = new ContactModel();
		
		$model->fields = Input::post();
		
        if(Request::isPost() && $model->isValid()) {
			
			View::render('success', $model);
			
        } else {
			
            View::render('index', $model);
        }
	}
}
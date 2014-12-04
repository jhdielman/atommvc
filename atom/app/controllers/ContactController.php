<?php

namespace Atom;

class ContactController extends Controller {
    
    public function index() {
        $model = new ContactModel();        
        return $this->view('index', $model);
    }
    
    public function postIndex() {
        $model = new ContactModel(Input::post());
        print_r($model->fields);
        print_r($model->rules);
        if($model->isValid()) {
            return $this->view('success', $model);
        } else {
            return $this->view('index', $model);
        }
    }
}
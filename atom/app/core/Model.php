<?php

namespace Atom;

abstract class Model {
    
    public $fields = [];
    
    public $rules = [];
    
    protected $validator;
    
    public function __construct() {
        
        $this->validator = new Validator($this->rules);
    }
    
    public function fieldValue($name, $value = null) {
        
        $argCount = func_num_args();
		$get = $argCount == 1;
		$set = $argCount == 2;

        if(isset($this->fields) && is_array($this->fields)) {
            
            if($get && array_key_exists($name, $this->fields)) {
                return  $this->fields[$name];
            } else if ($set) {
				if($value === null) {
					unset($this->fields[$name]);
				} else {
					$this->fields[$name] = $value;
				}
            }
        }
    }
    
    public function isValid() {
        
        return $this->validator->evaluate($this->fields);
    }
    
    public function fieldErrors($name, $wrapper = null, $class = null) {
        
        return $this->validator->getErrors($name, $wrapper, $class);
    }
}
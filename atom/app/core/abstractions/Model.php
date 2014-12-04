<?php

namespace Atom;

abstract class Model implements \JsonSerializable {
    
    const JsonDateFormat = "j/n/Y";
    
    public $fields = [];
    public $rules = [];
    protected $validator = null;
    
    public function __construct($data = null) {
        $this->validator = new Validator($this->rules);
        if($data) {
            $this->loadFields($data);
        }
    }
    
    protected function loadFields($data){
        foreach($this->fields as $field => $value) {
            if(array_key_exists($field, $data)) {
                $this->fields[$field] = $data[$field];
            } else {
                $this->fields[$field] = null;
            }
        }
    }
    
    public function fieldValue($name, $value = null) {
        
        $argCount = func_num_args();
        $get = $argCount == 1;
        $set = $argCount == 2;

        if(isset($this->fields) && is_array($this->fields)) {

            if($get && array_key_exists($name, $this->fields)) {
                $val = $this->fields[$name];
                
                if(is_string($val)) {
                    $val = Security::purify($val);
                    $this->fields[$name] = $val;
                }
                return  $this->fields[$name];
            } else if ($set) {
                if(is_string($value)) {
                    $value = Security::purify($value);
                }
                $this->fields[$name] = $value;
            }
        }
    }
    
    public function isEmpty($fieldNames = null) {
        $fields = $this->fields;
        
        if ($fieldNames) {
            $fields = array_intersect_key($fields, array_flip($fieldNames));
        }
        
        return empty(array_filter($fields));
    }

    public function isValid() {
        return $this->validator->evaluate($this->fields);
    }

    public function fieldErrors($name, $wrapper = null, $class = null) {
        return $this->validator->getErrors($name, $wrapper, $class);
    }

    public function jsonSerialize($autoSerializePublicProperties = false) {
        if ($autoSerializePublicProperties) {
            $properties = (new \ReflectionClass($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
            $modelClass = get_class();
            $out = [];
            
            if (!$properties) { 
                return null;
            }
            
            foreach ($properties as $o) {
                if ($o->class != $modelClass) {
                    $value = $o->getValue($this);
                    
                    if (is_string($value)) {
                        $value = Security::purify($value);
                    }
                    else if ($value instanceof \DateTime) {
                        $value = $value->format(static::JsonDateFormat);
                    }
                    
                    $out[$o->name] = $value;
                }
            }
            
            return (object)$out;
        }
        
        return null;
    }
 }
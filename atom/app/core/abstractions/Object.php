<?php

namespace Atom;

abstract class Object {

    protected $data = null;

    public function __construct($data = null) {
        $this->data = $data;
    }

    public function out() {
        return $this->data;
    }

    public function cast($type) {
       $newData = $this->data;
       if(is_array($newData)) {
           $newData = implode(',',$newData);
       }
       return new $type($newData);
    }


    protected function getCalledClass($includeNamespace = false) {

        $calledClass = get_called_class();
        if($includeNamespace == false) {
            $classHeirarchy = explode('\\',$calledClass);
            $calledClass = end($classHeirarchy);
        }
        return $calledClass;
    }
}
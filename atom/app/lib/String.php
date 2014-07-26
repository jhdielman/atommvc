<?php

 /**
 * AtomMVC: String Class
 * atom/app/lib/String.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * 
 */

namespace Atom;

class String extends Object {
        
    public function toUpper() {
        $this->data = strtoupper($this->data);
        return $this;
    }
    
    public function toLower() {
        $this->data = strtolower($this->data);
        return $this;
    }
    
    public function toCamelCase($capitalize = true, $delimiter = '-') {
        $str = $this->data;
		$str = str_replace($delimiter, ' ', $str);
		$str = ucwords($str);
		$str = str_replace(' ', '', $str);
		if(!$capitalize) {
			$str = lcfirst($str);
		}
        $this->data = $str;
		return $this;
	}
	
	public function camelToSnake() {
		$str = $this->data;
		$str[0] = strtolower($str[0]);
		$func = create_function('$c', 'return "_" . strtolower($c[1]);');
		$this->data = preg_replace_callback('/([A-Z])/', $func, $str);
		return $this;
	}
	
	public function lowerCaseFirst() {
		$this->data = lcfirst($this->data);
		return $this;
	}
	
	public function upperCaseFirst() {
		$this->data = ucfirst($this->data);
		return $this;
	}
    
    public function upperCaseWords() {
        $this->data = ucwords($this->data);
        return $this;
    }
    
    public function replace($old, $new) {
        $this->data = str_replace($old, $new, $this->data);
        return $this;
    }
	
	public function subStr($start,$length = null) {

		if(is_null($length)) {
			$this->data = substr($this->data, $start);
		} else {
			$this->data = substr($this->data, $start, $length);
		}
		
		return $this;
	}
	
	public function append($str) {
		$this->data = $this->data.$str;
		return $this;
	}
	
	public function prepend($str) {
		$this->data =  $str.$this->data;
		return $this;
	}
	
	public function join() {
	}
}
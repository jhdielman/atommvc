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
        $this->data = strtolower(preg_replace('/([A-Z])/', '_$1', $str));
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

    public function join($glue = '', $pieces = null) {
        if($pieces) {
            $str = '';
            if(is_string($pieces)) {
                $str = $pieces;
            } elseif (is_array($pieces)) {
                $str = implode($glue,$pieces);
            }
            $this->append($glue)->append($str);
        }
        return $this;
    }
    
    public function purify($str) {
        $this->data = Security::purify($this->data);
        return $this;
    }

    public function __toString() {
        return $this->data;
    }
    
    public static function splitString($value) {
        return preg_split("/\r?\n/", $value);
    }
}

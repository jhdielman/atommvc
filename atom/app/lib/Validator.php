<?php

/**
 * AtomMVC: Validator Class
 * atom/app/lib/Validator.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 */

namespace Atom;

class Validator {
    
	//*******************************************
	//* REGEX Ref
	//*******************************************
    //foo	The string "foo"
    //^foo	"foo" at the start of a string
    //foo$	"foo" at the end of a string
    //^foo$	"foo" when it is alone on a string
    //[abc]	a, b, or c
    //[a-z]	Any lowercase letter
    //[^A-Z]	Any character that is not a uppercase letter
    //(gif|jpg)	Matches either "gif" or "jpeg"
    //[a-z]+	One or more lowercase letters
    //[0-9\.\-]	Аny number, dot, or minus sign
    //^[a-zA-Z0-9_]{1,}$	Any word of at least one letter, number or _
    //([wx])([yz])	wy, wz, xy, or xz
    //[^A-Za-z0-9]	Any symbol (not a number or a letter)
    //([A-Z]{3}|[0-9]{4})	Matches three letters or four numbers
	
    public $errors = array();
    
    protected $rules = array();
    
    protected $requiredMessage = 'is required.';
	
    protected $emailMessage = 'is an invalid email format.';
	
    protected $urlMessage = 'is an invalid url.';
	
    protected $phoneMessage = 'is an invalid phone format.';
	
    protected $postalCodeMessage = 'is an invalid postal code format.';
	
    protected $patternMessage = 'is an invalid format.';
	
    protected $lengthMessage = 'is not the correct length.';
	
    protected $maxMessage = 'is too long.';
	
    protected $minMessage = 'is too short.';
	
    protected $rangeMessage = 'is out of range.';
	
    protected $intMessage = 'is not an integer.';
	
    protected $alphaMessage = 'is not a leter.';
	
    protected $numericMessage = 'is not a valid number.';
	
    protected $alphaNumericMessage = 'is not a number or letter.';
	
    protected $fileMessage = 'is an invalid file.';
	
    protected $fileTypeMessage = 'is an invalid file type.';
	
    protected $fileSizeMessage = 'is not the correct file size.';
    
    public function __construct($rules = array()) {
        
        foreach($rules as $name => $config) {
            
            if(array_key_exists('rules', $config)) {
                $rules[$name]['rules'] = $this->reorderRules($config['rules']);
            }
        }
        
        $this->rules = $rules;
    }

    public function required($value) {
        
        $valid = false;
        
        if (is_array($value)) {
			$valid = (bool) !empty($value);
		} else {
            $valid = (trim($value) == '') ? false : true;
		}
        
        return $valid;
    }
    
    public function email($value) {
        
        $valid = false;
        
        if(filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            $valid = true;
        }

        return $valid;
    }
    
    public function url($value) {
        
        $valid = false;
        
        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $valid = true;
        }
        
        return $valid;
    }
    
    public function blank($value) {
        
        $valid = false; 
        
        $pattern = '^$';
        
        $valid = $this->pattern($value, $pattern);
        
        return $valid;
    }
    
    public function phone($value) {
        
        $valid = false;
        
        $pattern = "/^\(\d{3}\) ?\d{3}( |-|.)?\d{4}|^\d{3}( |-|.)?\d{3}( |-|.)?\d{4}/";
        
        $valid = $this->pattern($value, $pattern);
        
        return $valid;
    }
    
    public function postalCodeUS($value) {
        
        $valid = false;
        
        $pattern = "/[0-9]{5}|[0-9]{5}( |-)[0-9]{4}/";
        
        $valid = $this->pattern($value, $pattern);
        
        return $valid;
    }
    
    public function pattern($value, $pattern) {
        
        $valid = false;
        
        if (preg_match($pattern, $value) === 1) {
            $valid = true;
        }
        
        return $valid;
    }
    
    public function length($value, $length) {
        
        $valid = false; 

        if($this->numeric($length)) {
            
            $func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
            $valid = ((int) $func($value)) === ((int) $length);
        }
        
        return $valid;
    }
    
    public function max($value, $max) {
        
        $valid = false; 

        if($this->numeric($max)) {
            
            $func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
            $valid = ((int) $func($value)) <= ((int) $max);
        }
        
        return $valid;
    }
    
    public function min($value, $min) {
        
        $valid = false; 

        if($this->numeric($min)) {
            
            $func = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
            $valid = ((int) $func($value)) >= ((int) $min);
        }
        
        return $valid;
    }
    
    public function range($value, $params) {
        
        $valid = false;
        
        if(!is_array($params)) {
            $params = explode(',', trim($params));
        }
        
        $count = count($params);
        
        if($count && $count < 3) {
            
            if($count == 1) {
                $params[] = 0;
            }
            
            $validMin = $this->min($value, min($params));
            $validMax = $this->max($value,  max($params));
            $valid = $validMin && $validMax;
        }
        
        return $valid;
    }
    
    public function int($value) {
        
        $valid = false;
        
        if($this->numeric($value)) {
            $valid = (bool) (intval($value) == floatval($value));
        }
        
        return $valid;
    }
    
    public function alpha($value) {
        
    }
    
    public function numeric($value) {
        
        $valid = false;
        
        if(is_numeric(trim($value))) {
            $valid = true;
        }
        
        return $valid;
    }
    
    public function alphaNumeric($value) {
        
        $valid = false;
        
        if(ctype_alnum($value)) {
            $valid = true;
        }
        
        return $valid;
    }
    
    public function file($value, $params) {
    }
    
    public function fileType($value, $type) {
    }
    
    public function fileSize($value, $size) {
    }
    
    // *** REFERENCE FOR POSTED FILES *** //
    //$_FILES["file"]["name"] - the name of the uploaded file
    //$_FILES["file"]["type"] - the type of the uploaded file
    //$_FILES["file"]["size"] - the size in bytes of the uploaded file
    //$_FILES["file"]["tmp_name"] - the name of the temporary copy of the file stored on the server
    //$_FILES["file"]["error"] - the error code resulting from the file upload
    
    public function evaluate($fields) {
        
        $valid = true;
        $requiredKey = 'required';
        
        if($fields instanceof Collection) {
            $fields = $fields->out();
        }
        
        if(is_array($fields)) {

            foreach($this->rules as $name => $ruleConfig) {
                
                if(array_key_exists($name, $fields)) {
                    
                    $this->errors[$name] = array();
                    $label = $ruleConfig['label'];
                    $rules = $ruleConfig['rules'];
                    $value = $fields[$name];
                    $valueEmpty = empty($value);
                    
                    $required = array_key_exists($requiredKey, $rules) && $rules[$requiredKey];
                    
                    if($valueEmpty && !$required) {
                        
                        $valid = true;
                        
                    } else {
                        
                        $pass = false;
                        
                        if($required) {
                            
                            $pass = $this->required($value);
                            
                            $this->updateErrors($pass, $name, $requiredKey, $label);
                            
                        } else {
                            
                            foreach($rules as $rule => $params) {
                                
                                $pass = $this->{$rule}($value, $params);
                                
                                $this->updateErrors($pass, $name, $rule, $label);
                            }
                        }
                        
                        $valid = $valid && $pass;
                    }
                }
            }
        }
        
        return $valid;
    }
    
    protected function updateErrors($pass, $name, $rule, $label) {
        
        if($pass === false) {
            $this->errors[$name][] = $this->compileErrorMessage($rule, $label);
        }
    }
    
    protected function reorderRules($rules) {

        $required = false;
        foreach($rules as $k => $v) {
            if(is_int($k)) {
                unset($rules[$k]);
                if($v == 'required') {
                    $required = true;
                } else {
                    $rules[$v] = true;
                }
                
            } else if($k == 'required') {
                unset($rules[$k]);
                $required = $v;
            }
        }
        
        if($required) {
            $rules['required'] = true;
            $rules = array_reverse($rules);
        }
        
        return $rules;
    }
    
    protected function compileErrorMessage($rule, $label) {
        
        $messageProperty = $rule.'Message';;
        $message;

        if(!property_exists($this, $messageProperty)) {
            $message = 'is invalid';
        } else {
            $message = $this->{$messageProperty};
        }
        
        return "$label $message";
    }
    
    public function getErrors($key, $wrapper = null, $class = null) {
        
        $errorString = '';
        
        $class = $class ? "class=\"$class\"" : '';
       
        if(array_key_exists($key, $this->errors) && count($this->errors[$key])) {
            
            if($wrapper) {
                
                foreach($this->errors[$key] as $error) {
                    
                    $errorString .= "<$wrapper $class>$error</$wrapper>";
                }
                
            } else {
                $errorString = implode('<br>', $this->errors[$key]);
            }
        }
        
        return $errorString;
    }
}
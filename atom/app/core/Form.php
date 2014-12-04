<?php

namespace Atom;

class Form {
    public static function labelFor(Model $model, $fieldName) {
        $fullFieldId = $model->getFullFieldId($fieldName);
        $label = static::getFieldLabel($model, $fieldName);
        
        return "<label for=\"$fullFieldId\">$label</label>";
    }
    
    public static function textFor(Model $model, $fieldName, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        return static::inputFor($model, $fieldName, "text", null, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function emailFor(Model $model, $fieldName, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        return static::inputFor($model, $fieldName, "email", null, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function passwordFor(Model $model, $fieldName, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        return static::inputFor($model, $fieldName, "password", null, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function hiddenFor(Model $model, $fieldName, Array $attributes = null, $includeErrors = true) {
        return static::inputFor($model, $fieldName, "hidden", null, $attributes, false, $includeErrors);
    }
    
    public static function dateFor(Model $model, $fieldName, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        return static::inputFor($model, $fieldName, "date", null, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function textForArray(Model $model, $fieldName, $index, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        return static::inputForArray($model, $fieldName, "text", $index, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function inputForArray(Model $model, $fieldName, $type, $index, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        $valueArray = $model->fieldValue($fieldName);
        $value = null;
        
        if (!empty($valueArray)) {
            if ($valueArray instanceof Collection) {
                $valueArray = $valueArray->out();
            }
            
            if (is_array($valueArray) && array_key_exists($index, $valueArray)) {
                $value = $valueArray[$index];
            }
        }
        
        //If there is no value, use an empty string otherwise inputFor will attempt to get the value from the model.
        $value = ($value ?: "");

        return static::inputFor($model, $fieldName, $type, $value, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function inputFor(Model $model, $fieldName, $type, $value = null, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        $fullFieldId = $model->getFullFieldId($fieldName);
        $fullFieldName = $model->getFullFieldName($fieldName);
        $value = ($value ?: $model->fieldValue($fieldName));
        $html = "";
        
        if ($includeLabel === true) {
            $html .= static::labelFor($model, $fieldName);
        }
        
        $html .= "<input id=\"$fullFieldId\" name=\"$fullFieldName\" type=\"$type\"";
        
        if (!empty($value)) {
            $html .= " value=\"$value\"";
        }
        
        $html .= static::getAttributesString($attributes);
        $html .= "/>";
        
        if ($includeErrors === true) {
            $html .= static::errorsFor($model, $fieldName);
        }
        
        return $html;
    }

    public static function textAreaFor(Model $model, $fieldName, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        $fullFieldId = $model->getFullFieldId($fieldName);
        $fullFieldName = $model->getFullFieldName($fieldName);
        $value = $model->fieldValue($fieldName);
        $html = "";
        
        if ($includeLabel === true) {
            $html .= static::labelFor($model, $fieldName);
        }
        
        $html .= "<textarea id=\"$fullFieldId\" name=\"$fullFieldName\"";
        
        if (!empty($value)) {
            $html .= " value=\"$value\"";
        }
        
        $html .= static::getAttributesString($attributes);
        $html .= "></textarea>";
        
        if ($includeErrors === true) {
            $html .= static::errorsFor($model, $fieldName);
        }
        
        return $html;
    }
    
    public static function selectFor(Model $model, $fieldName, ListModel $list = null, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        $fullFieldId = $model->getFullFieldId($fieldName);
        $fullFieldName = $model->getFullFieldName($fieldName);
        $html = "";
        
        if ($includeLabel === true) {
            $html .= static::labelFor($model, $fieldName);
        }
        
        $html .= "<select id=\"" . $fullFieldId . "\" name=\"" . $fullFieldName . "\"";
        $html .= static::getAttributesString($attributes);
        $html .= ">";
        
        if (!empty($list) && !empty($list->items)) {
            $items = $list->items;
            
            if (!empty($items)) {
                foreach ($items as $item) {
                    $html .= "<option value=\"{$item->value}\"" . ($item->selected === true ? " selected=\"selected\"" : "") . "\">{$item->text}</option>";
                }
            }
        }
        
        $html .= "</select>";
        
        if ($includeErrors === true) {
            $html .= static::errorsFor($model, $fieldName);
        }
        
        return $html;
    }
    
    public static function stateSelectFor(Model $model, $fieldName, $countryCode = 'USA', Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        $codes = Config::get('locations','codes');
        $countries = Config::get('locations','countries');
        $selectedValue = $model->fieldValue($fieldName);
        
        if(empty($countryCode)) {
            $countryCode = 'USA';
        }
        
        if(strlen($countryCode) == 2) {
            $countryCode = $codes[$countryCode];
        }
        
        $statesProvinces = $countries[$countryCode]['statesProvinces'];
        $stateListItems = [];
        
        foreach($statesProvinces as $abbr => $state) {
            $selected = (!empty($selectedValue) && $selectedValue == $abbr);
            $stateListItems[] = new ListItemModel($abbr, $state, $selected);
        }
        
        $statesListModel = new ListModel($stateListItems);
        
        return static::selectFor($model, $fieldName, $statesListModel, $attributes, $includeLabel, $includeErrors);
    }
    
    public static function checkboxFor(Model $model, $fieldName, Array $attributes = null, $includeLabel = true, $includeErrors = true) {
        $fullFieldId = $model->getFullFieldId($fieldName);
        $fullFieldName = $model->getFullFieldName($fieldName);
        $value = $model->fieldValue($fieldName);
        $html = "";
        
        if ($includeLabel === true) {
            $label = static::getFieldLabel($model, $fieldName);
            $html = "<label for=\"$fullFieldId\">";
        }
        
        $html = "<input id=\"$fullFieldId\" name=\"$fullFieldName\" type=\"checkbox\"";
        
        if (!empty($value)) {
            $html .= " checked=\"checked\"";
        }

        $html .= static::getAttributesString($attributes);
        $html .= "/>";
        
        if ($includeLabel === true) {
            $html .= "$label</label>";
        }
        
        if ($includeErrors === true) {
            $html .= static::errorsFor($model, $fieldName);
        }
        
        return $html;
    }
    
    public static function errorsFor(Model $model, $fieldName) {
        return $model->fieldErrors($fieldName);
    }
    
    public static function getFieldLabel(Model $model, $fieldName) {
        $label = $fieldName;
        
        if (!empty($model->rules) && array_key_exists($fieldName, $model->rules) && array_key_exists("label", $model->rules[$fieldName])) {
        	$label = $model->rules[$fieldName]["label"];
        }
        
        return $label;
    }
    
    public static function getFieldId(Model $model, $fieldName) {
        return $model->getFullFieldId($fieldName);
    }
    
    public static function getFieldName(Model $model, $fieldName) {
        return $model->getFullFieldName($fieldName);
    }
    
    public static function getFieldIdForArray(Model $model, $fieldName, $index) {
        return $model->getFullFieldIdForArray($fieldName, $index);
    }
    
    public static function getFieldNameForArray(Model $model, $fieldName, $index) {
        return $model->getFullFieldNameForArray($fieldName, $index);
    }
    
    private static function getAttributesString(Array $attributes = null) {
        $html = "";

        if ($attributes != null) {
        	foreach ($attributes as $key => $value) {
        		$html .= " $key=\"$value\"";
        	}
        }
        
        return $html;
    }
}
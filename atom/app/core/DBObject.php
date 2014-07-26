<?php

namespace Atom;

abstract class DBObject extends Object {
    
	protected $table;
	
	protected $queryBuilder;
	
	protected $primaryKey;
	
	protected $hidden = [];
	
	protected $required = [];
	
	protected $columns = [];
	
	protected $excluded = ['add_date','last_modified'];
	
	protected $searchKeys = [];
	
	protected $activeKey = 'active';
	
	protected $softDelete = false;
	
	public $exists = false;
	
	public function __construct($properties = array(), $overwrite = false) {
		parent::__construct($properties);
		$this->loadExisting($properties, $overwrite);
	}
	
	public static function all($columns = array('*')) {
		
		$instance = new static;

		return $instance->queryBuilder()->get($columns);
	}
	
	public static function find($id, $columns = array('*')) {

		$instance = new static;

		return $instance->queryBuilder()->find($id, $columns);
	}
	
	public static function create(array $properties) {
		
		$obj = new static($properties);

		$obj->save();
		
		return $obj;
	}
	
	public function save() {
		
		return $this->insertOrUpdate();
	}
	
	public function update() {
		
		return $this->insertOrUpdate();
	}
	
	public function insert() {
		
		return $this->insertOrUpdate();
	}
	
	public function delete() {
		
		$deleted = false;
		
		if($this->exists) {
			
			if($this->softDeleteEnabled()) {

				$this->value($this->activeKey, 0);
			
				$updateValues = $this->getUpdateValues();
				
				print_r($updateValues);

				$deleted = $this->queryBuilder()->update($updateValues);
				
			} else {
				$this->queryBuilder()->delete();
				//$primaryKey = $this->getPrimaryKey();
				//$primaryKeyCount = count($primaryKey);
				//
				//if($primaryKeyCount && $primaryKeyCount == 1) {
				//
				//	$pk = $primaryKey[0];
				//	
				//	$deleted = $this->queryBuilder()
				//		->where($pk,$this->value($pk))
				//		->delete();
				//	
				//} else if ($primaryKeyCount && $primaryKeyCount > 1) {
				//
				//	$params = [];
				//	
				//	foreach($primaryKey as $key) {
				//		$params[":$key"] = $this->value($key);
				//	}
				//	
				//	$where = implode(',',$primaryKey);
				//	$in = implode(',',array_keys($params));
				//	
				//	$table = Config::get('db','tablePrefix').$this->getTable();
				//	$query = "DELETE FROM $table WHERE ($where) IN (($in));";
				//	$expression = new Expression($query);
				//	$deleted = $this->queryBuilder()
				//		->runRaw($expression, $params, 'statementWithRows');
				//}	
			}
		}
		
		return $deleted;
	}
	
	protected function isVisible($key) {
		
		$visible = true;
		
		if(count($this->hidden)) {
			$visible = !(in_array($key,$this->hidden));
		}
		
		return $visible;
	}
	
	public function value($key, $value = null) {
		
		$argCount = func_num_args();
		$get = $argCount == 1;
		$set = $argCount == 2;

        if(isset($this->data) && is_array($this->data)) {
            
            if($get && array_key_exists($key, $this->data)) {
                return  ($this->isVisible($key) ? $this->data[$key] : '');
            } else if ($set) {
				if($value === null) {
					unset($this->data[$key]);
				} else {
					$this->data[$key] = $value;
				}
            }
        }
	}
	
	protected function insertOrUpdate() {
		
		$saved;
		
		// If the model already exists in the database we can just update our record
		// that is already in this database using the current IDs in this "where"
		// clause to only update this model. Otherwise, we'll just insert them.
		if ($this->exists) {
			
			$updateValues = $this->getUpdateValues();
			
			$saved = $this->queryBuilder()->update($updateValues);
			
		// If the model is brand new, we'll insert it into our database and set the
		// ID attribute on the model to the value of the newly inserted row's ID
		// which is typically an auto-increment value managed by the database.
		} else if(!$this->exists && $this->requiredFieldsResolved()) {
				
			$insertValues = $this->getInsertValues();
	
			$saved = $this->queryBuilder()->insertGetId($insertValues);
		}
		
		return ((bool) $saved);
	}
	
	protected function getUpdateValues() {
		
		$data = $this->data;

		$excluded = $this->getExcluded();
		
		foreach($excluded as $key) {
			unset($data[$key]);
		}
		
		return $data;
	}
	
	protected function getInsertValues() {
		
		$data = $this->data;
		
		$excluded = $this->getExcluded();
		
		foreach($excluded as $key) {
			unset($data[$key]);
		}
		
		return $data;
	}
	
	protected function getExcluded() {
		$excluded = $this->excluded;
		$excluded[] = $this->getPrimaryKey();
		return $excluded;
	}
	
	protected function requiredFieldsResolved() {
		$requiredKeysInData = array_intersect($this->required, $this->getInsertValues());
		$hasRequired = count($this->required) == count($requiredKeysInData);
		return ((bool) $hasRequired);
	}
	
	protected function loadExisting($properties = array(), $overwrite = false) {
		
		$propKeys = array_keys($properties);

		if(count(array_intersect($propKeys,$this->getSearchKeys()))) {
			
			$existing = $this->searchExisting($properties);
			
			if($existing) {
				
				$this->exists = true;
				
				if($overwrite) {
					$this->data = array_merge($existing,$properties);
				} else {
					$this->data = $existing;
				}
			}
		}
	}
	
	protected function searchExisting($properties) {
		
		$qb = $this->queryBuilder();
		
		foreach($properties as $key => $val) {
			$qb->where($key, $val);	
		}
		
		return $qb->first();
	}
	
	protected function queryBuilder() {
		
		if(!$this->queryBuilder) {
			
			$this->queryBuilder = new QueryBuilder($this->getTable());
		}
		
		return $this->queryBuilder;
	}
	
	protected function getTable() {
		
		if(!$this->table) {
			$obj = $this->getCalledClass();
			$table = (new String($obj))
				->camelToSnake()
				->out();
			$tablePieces = explode('_',$table);
			$tableEnd = Pluralizer::plural(end($tablePieces));
			array_pop($tablePieces);
			$tablePieces[] = $tableEnd;
			$table = implode('_', $tablePieces);
			$this->table = $table;
		}
		
		return $this->table;
	}
	
	protected function getColumns() {
		
		if(!$this->columns) {
			$table = Config::get('db','tablePrefix').$this->getTable();
			$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table';";
			$expression = new Expression($query);
			$columns = $this->queryBuilder()
				->runRaw($expression);
			foreach($columns as $column) {
				$this->columns[] = $column['COLUMN_NAME'];
			}
		}

		return $this->columns;
	}
	
	protected function getPrimaryKey() {
		
		return $this->queryBuilder()->getPrimaryKey();
		//if(!$this->primaryKey) {
		//	$table = Config::get('db','tablePrefix').$this->getTable();
		//	$query = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY';";
		//	$expression = new Expression($query);
		//	$keys = $this->queryBuilder()
		//		->runRaw($expression);
		//	foreach($keys as $key) {
		//		$this->primaryKey[] = $key['Column_name'];
		//	}
		//}
		//
		//return $this->primaryKey;
	}
	
	protected function getSearchKeys() {
		
		$searchKeys = $this->searchKeys;
		$searchKeys[] = $this->getPrimaryKey();
		return array_unique($searchKeys);
	}
	
	protected function softDeleteEnabled() {
		
		$canSetInactive = in_array($this->activeKey,$this->getColumns());
		
		return ((bool) ($canSetInactive && $this->softDelete));
	}
	
	public function out() {
		
		$data = $this->data;
		
		foreach($this->hidden as $key) {
			unset($data[$key]);
		}
		
		return $data;
	}
}
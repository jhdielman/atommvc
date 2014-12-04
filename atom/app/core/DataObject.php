<?php

namespace Atom;

class DataObject extends Object implements \JsonSerializable {
    
    protected $tablePrefix;
    
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

    public function __construct($properties = [], $exists = false, $overwrite = false) {
        parent::__construct($properties);
        
        $propCount = count($properties);
        
        if(count($properties)) {
            if($exists !== true) {
                $this->loadExisting($properties, $overwrite);
            } else {
                $this->exists = $exists;
            }
        }
        
        $this->initializeQueryBuilder($properties);
    }
    
    protected function initializeQueryBuilder(array $properties) {
        $qb = $this->queryBuilder();
        
        foreach($properties as $key => $value) {
            $qb->where($key,$value);
        }
    }
    
    public static function find($id, $columns = ['*']) {
        $qb = static::createQueryBuilder();
        $queryResult = $qb->find($id, $columns);
        
        if ($queryResult != null) {
            return new static($queryResult, true);
        }
        
        return null;
    }

    public static function all($columns = ['*'], $raw = false) {
        $qb = static::createQueryBuilder();
        $queryResults = $qb->get($columns);
        
        return static::collectionFromExisting($queryResults);
    }
    
    public static function select($columns = array('*')) {
        $qb = static::createQueryBuilder();
        return $qb->select($columns);
    }
    
    public static function distinct() {
        $qb = static::createQueryBuilder();
        return $qb->distinct();
    }

    public static function groupBy() {
        $qb = static::createQueryBuilder();
        return $qb->groupBy();
    }

    public static function having($column, $operator = null, $value = null, $boolean = 'and') {
        $qb = static::createQueryBuilder();
        return $qb->having($column, $operator, $value, $boolean);
    }
    
    public static function orderBy($column, $direction = 'asc') {
        $qb = static::createQueryBuilder();
        return $qb->orderBy($column, $direction);
    }

    public static function latest($column = 'add_date') {
        $qb = static::createQueryBuilder();
        return $qb->latest($column);
    }

    public static function oldest($column = 'add_date') {
        $qb = static::createQueryBuilder();
        return $qb->oldest($column);
    }

    public static function offset($value) {
        $qb = static::createQueryBuilder();
        return $qb->offset($value);
    }

    public static function skip($value) {
        $qb = static::createQueryBuilder();
        return $qb->skip($value);
    }

    public static function limit($value) {
        $qb = static::createQueryBuilder();
        return $qb->limit($value);
    }

    public static function take($value) {
        $qb = static::createQueryBuilder();
        return $qb->take($value);
    }
    
    public static function pluck($column) {
        $qb = static::createQueryBuilder();
        return $qb->pluck($column);
    }

    public static function first($columns = ['*']) {
        $qb = static::createQueryBuilder();
        return $qb->first($columns);
    }
    
    public static function count($columns = ['*']) {
        $qb = static::createQueryBuilder();
        return $qb->first($columns);
    }
    
    public static function where($column, $operator = null, $value = null, $boolean = 'and') {
        $qb = static::createQueryBuilder();
        return $qb->where($column, $operator, $value, $boolean);
    }

    public static function create(array $properties) {
        $instance = new static($properties);
        $instance->save();
        return $instance;
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
                $this->set($this->activeKey, 0);
                $updateValues = $this->getUpdateValues();
                $deleted = $this->queryBuilder()->update($updateValues);
            } else {
                $this->queryBuilder()->delete();
            }
        }

        return $deleted;
    }
    
    public static function populate(array $items, $fromQuery = true) {
        $instanceItems = [];
        if(is_array($items) && count($items) && !($items[0] instanceof static)) {
            foreach($items as $item) {
                array_push($instanceItems, (new static($item, $fromQuery)));
            }
        }
        return new Collection($instanceItems);
    }


    protected function isVisible($key) {
        
        $visible = true;
        
        if(count($this->hidden)) {
            $visible = !(in_array($key,$this->hidden));
        }
        
        return $visible;
    }

    public function get($key) {
        if(isset($this->data) && is_array($this->data)) {
            if(array_key_exists($key, $this->data)) {
                return  ($this->isVisible($key) ? $this->data[$key] : '');
            }
        }
    }

    public function set($key, $value) {
        if(isset($this->data) && is_array($this->data)) {
            $this->data[$key] = $value;
        }
    }

    protected function insertOrUpdate() {

        $pk = $this->getPrimaryKey();
        $saved = false;

        if ($this->exists) {
            $pkval = $this->get($pk);

            if($pkval) {
                $saved = static::createQueryBuilder()
                    ->where($pk, $pkval)
                    ->update($this->getUpdateValues());
            }
            
        } else if(!$this->exists && $this->requiredFieldsResolved()) {

            $insertValues = $this->getInsertValues();

            $newId = $this->queryBuilder()->insertGetId($insertValues);
            
            if (isset($newId)) {
                $this->set($pk, $newId);
                $this->exists = true;
                $saved = true;
            }
        }

        return ((bool) $saved);
    }

    protected function getUpdateValues() {

        $data = $this->data;

        $excluded = $this->getExcluded();

        foreach($excluded as $key) {
            unset($data[$key]);
        }
        
        $data = $this->purify($data);
        $data = $this->getUnderlyingValues($data);

        return $data;
    }

    protected function getInsertValues() {

        $data = $this->data;

        $excluded = $this->getExcluded();

        foreach($excluded as $key) {
            unset($data[$key]);
        }
        
        $data = $this->purify($data);
        $data = $this->getUnderlyingValues($data);

        return $data;
    }

    protected function getExcluded() {
        $excluded = $this->excluded;
        $excluded[] = $this->getPrimaryKey();
        return $excluded;
    }

    protected function requiredFieldsResolved() {
        $required = $this->required;
        $insertValues = $this->getInsertValues();
        $requiredKeysInData = array_intersect($required, $insertValues);
        $hasRequired = (count($this->required) == count($requiredKeysInData));
        
        return ((bool) $hasRequired);
    }

    protected function loadExisting($properties = array(), $overwrite = false) {

        $searchProperties = [];
        $searchKeys = $this->getSearchKeys();

        foreach($searchKeys as $searchKey) {
            if(array_key_exists($searchKey,$properties)) {
                $searchProperties[$searchKey] = $properties[$searchKey];
            }
        }

        if(count($searchProperties)) {
            
            $existing = $this->searchExisting($searchProperties);
            
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

    public function queryBuilder() {
        
        if(!$this->queryBuilder) {
            $this->queryBuilder = new QueryBuilder($this->getTable());
        }
        
        return $this->queryBuilder;
    }
    
    public static function createQueryBuilder() {
        return new QueryBuilder(static::TableName);
    }
    
    protected function getTablePrefix() {
        if(!$this->tablePrefix) {
            $this->tablePrefix == Config::get('database','tablePrefix');
        }
        return $this->tablePrefix;
    }

    protected function getTable() {

        if(!$this->table) {
            $obj = $this->getCalledClass();
            $table = (new String($obj))
                ->camelToSnake()
                ->out();
            $pieces = explode('_',$table);
            $tableEnd = Pluralizer::plural(end($pieces));
            array_pop($pieces);
            $pieces[] = $tableEnd;
            $table = implode('_', $pieces);
            $this->table = $table;
        }

        return $this->table;
    }

    protected function getColumns() {

        if(!$this->columns) {
            $table = $this->getTablePrefix().$this->getTable();
            $query = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = '$table';";
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

    public function publicData() {

        $publicData = $this->data;

        foreach($this->hidden as $key) {
            unset($publicData[$key]);
        }

        return $publicData;
    }

    public function out() {

        return $this->publicData();
    }

    public function jsonSerialize() {

        return $this->publicData();
    }
    
    public function toJson() {
        return json_encode($this);
    }
}
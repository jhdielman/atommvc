<?php

/**
 * AtomMVC: QueryBuilder Class
 * atom/app/lib/QueryBuilder.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 * 
 * @see https://github.com/illuminate/database/blob/master/Query/Builder.php
 *
 */

namespace Atom;

use Closure;
use PDO;

class QueryBuilder /*extends PDO*/ {
	
    /**
     * The database connection instance.
     *
     * @var \PDO
     */
	protected static $pdo;
	
	/**
     * The database connection instance.
     *
     * @var string
     */
	protected $primaryKey;
	
    /**
     * The default logging flag
     *
     * @var constant;
     */
	protected $logging = false;
    
    /**
     * The default PDO Fetch mode
     *
     * @var Atom\QueryBuilder
     */
	protected $fetchMode;

	/**
     * The database query grammar instance.
     *
     * @var Atom\MySqlGrammar
     */
	protected $grammar;

	/**
     * The current query value bindings.
     *
     * @var array
     */
	protected $bindings = array(
		'select' => [],
		'join'   => [],
		'where'  => [],
		'having' => [],
		'order'  => [],
	);

	/**
     * An aggregate function and column to be run.
     *
     * @var array
     */
	public $aggregate;

	/**
     * The columns that should be returned.
     *
     * @var array
     */
	public $columns;

	/**
     * Indicates if the query returns distinct results.
     *
     * @var bool
     */
	public $distinct = false;

	/**
     * The table which the query is targeting.
     *
     * @var string
     */
	public $from;

	/**
     * The table joins for the query.
     *
     * @var array
     */
	public $joins;

	/**
     * The where constraints for the query.
     *
     * @var array
     */
	public $wheres;

	/**
     * The groupings for the query.
     *
     * @var array
     */
	public $groups;

	/**
     * The having constraints for the query.
     *
     * @var array
     */
	public $havings;

	/**
     * The orderings for the query.
     *
     * @var array
     */
	public $orders;

	/**
     * The maximum number of records to return.
     *
     * @var int
     */
	public $limit;

	/**
     * The number of records to skip.
     *
     * @var int
     */
	public $offset;

	/**
     * The query union statements.
     *
     * @var array
     */
	public $unions;

	/**
     * Indicates whether row locking is being used.
     *
     * @var string|bool
     */
	public $lock;

	/**
     * The key that should be used when caching the query.
     *
     * @var string
     */
	protected $cacheKey;

	/**
     * The number of minutes to cache the query.
     *
     * @var int
     */
	protected $cacheMinutes;

	/**
     * The tags for the query cache.
     *
     * @var array
     */
	protected $cacheTags;

	/**
     * The cache driver to be used.
     *
     * @var string
     */
	protected $cacheDriver;

	/**
     * All of the available clause operators.
     *
     * @var array
     */
	protected $operators = array(
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'not like', 'between', 'ilike',
		'&', '|', '^', '<<', '>>',
	);

	/**
     * Create a new query builder instance.
     *
     * @return void
     */
    
    public function __construct($table = null) {
        
        $this->fetchMode = PDO::FETCH_ASSOC;
		$this->grammar = new MySqlGrammar();
        $this->from = $table;
	}

	/**
     * Set the columns to be selected.
     *
     * @param  array  $columns
     * @return Atom\QueryBuilder|static
     */
	public function select($columns = array('*')) {

		$this->columns = is_array($columns) ? $columns : func_get_args();
		return $this;
	}

	/**
     * Add a new "raw" select expression to the query.
     *
     * @param  string  $expression
     * @return Atom\QueryBuilder|static
     */
	public function selectRaw($expression) {

		return $this->select(new Expression($expression));
	}

	/**
     * Add a new select column to the query.
     *
     * @param  mixed  $column
     * @return Atom\QueryBuilder|static
     */
	public function addSelect($column) {

		$column = is_array($column) ? $column : func_get_args();
		$this->columns = array_merge((array) $this->columns, $column);
        
		return $this;
	}

	/**
     * Force the query to only return distinct results.
     *
     * @return Atom\QueryBuilder|static
     */
	public function distinct() {

		$this->distinct = true;
		return $this;
	}

	/**
     * Set the table which the query is targeting.
     *
     * @param  string  $table
     * @return Atom\QueryBuilder|static
     */
	public function from($table) {

		$this->from = $table;
		return $this;
	}

	/**
     * Add a join clause to the query.
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $two
     * @param  string  $type
     * @param  bool  $where
     * @return Atom\QueryBuilder|static
     */
	public function join($table, $one, $operator = null, $two = null, $type = 'inner', $where = false) {
        
		// If the first "column" of the join is really a Closure instance the developer
		// is trying to build a join with a complex "on" clause containing more than
		// one condition, so we'll add the join and call a Closure with the query.
		if ($one instanceof Closure) {
			$this->joins[] = new JoinClause($this, $type, $table);
			call_user_func($one, end($this->joins));

        // If the column is simply a string, we can assume the join simply has a basic
        // "on" clause with a single condition. So we will just build the join with
        // this simple join clauses attached to it. There is not a join callback.
        } else {
			$join = new JoinClause($this, $type, $table);
			$this->joins[] = $join->on(
				$one, $operator, $two, 'and', $where
			);
		}

		return $this;
	}

	/**
     * Add a "join where" clause to the query.
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $two
     * @param  string  $type
     * @return Atom\QueryBuilder|static
     */
	public function joinWhere($table, $one, $operator, $two, $type = 'inner') {

		return $this->join($table, $one, $operator, $two, $type, true);
	}

	/**
     * Add a left join to the query.
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $second
     * @return Atom\QueryBuilder|static
     */
	public function leftJoin($table, $first, $operator = null, $second = null) {

		return $this->join($table, $first, $operator, $second, 'left');
	}

	/**
     * Add a "join where" clause to the query.
     *
     * @param  string  $table
     * @param  string  $first
     * @param  string  $operator
     * @param  string  $two
     * @return Atom\QueryBuilder|static
     */
	public function leftJoinWhere($table, $one, $operator, $two) {

		return $this->joinWhere($table, $one, $operator, $two, 'left');
	}

	/**
     * Add a basic where clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     *
     * @throws \InvalidArgumentException
     */
	public function where($column, $operator = null, $value = null, $boolean = 'and') {
        
		if (func_num_args() == 2) {
			list($value, $operator) = array($operator, '=');
		} elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new \InvalidArgumentException("Value must be provided.");
		}

		// If the columns is actually a Closure instance, we will assume the developer
		// wants to begin a nested where statement which is wrapped in parenthesis.
		// We'll add that Closure to the query then return back out immediately.
		if ($column instanceof Closure) {
			return $this->whereNested($column, $boolean);
		}

		// If the given operator is not found in the list of valid operators we will
		// assume that the developer is just short-cutting the '=' operators and
		// we will set the operators to '=' and set the values appropriately.
		if (!in_array(strtolower($operator), $this->operators, true)) {
			list($value, $operator) = array($operator, '=');
		}

		// If the value is a Closure, it means the developer is performing an entire
		// sub-select within the query and we will need to compile the sub-select
		// within the where clause to get the appropriate query record results.
		if ($value instanceof Closure) {
			return $this->whereSub($column, $operator, $value, $boolean);
		}

		// If the value is "null", we will just assume the developer wants to add a
		// where null clause to the query. So, we will allow a short-cut here to
		// that method for convenience so the developer doesn't have to check.
		if (is_null($value)) {
			return $this->whereNull($column, $boolean, $operator != '=');
		}

		// Now that we are working with just a simple query we can put the elements
		// in our array and add the query binding to our array of bindings that
		// will be bound to each SQL statements when it is finally executed.
		$type = 'Basic';

		$this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

		if (!$value instanceof Expression) {
			$this->addBinding($value, 'where');
		}

		return $this;
	}

	/**
     * Add an "or where" clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return Atom\QueryBuilder|static
     */
	public function orWhere($column, $operator = null, $value = null) {
        
		return $this->where($column, $operator, $value, 'or');
	}

	/**
     * Determine if the given operator and value combination is legal.
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
	protected function invalidOperatorAndValue($operator, $value) {
        
		$isOperator = in_array($operator, $this->operators);

		return ($isOperator && $operator != '=' && is_null($value));
	}

	/**
     * Add a raw where clause to the query.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereRaw($sql, array $bindings = array(), $boolean = 'and') {
        
		$type = 'raw';

		$this->wheres[] = compact('type', 'sql', 'boolean');

		$this->addBinding($bindings, 'where');

		return $this;
	}

	/**
     * Add a raw or where clause to the query.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return Atom\QueryBuilder|static
     */
	public function orWhereRaw($sql, array $bindings = array()) {
        
		return $this->whereRaw($sql, $bindings, 'or');
	}

	/**
     * Add a where between statement to the query.
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return Atom\QueryBuilder|static
     */
	public function whereBetween($column, array $values, $boolean = 'and', $not = false) {
        
		$type = 'between';

		$this->wheres[] = compact('column', 'type', 'boolean', 'not');

		$this->addBinding($values, 'where');

		return $this;
	}

	/**
     * Add an or where between statement to the query.
     *
     * @param  string  $column
     * @param  array   $values
     * @return Atom\QueryBuilder|static
     */
	public function orWhereBetween($column, array $values) {
        
		return $this->whereBetween($column, $values, 'or');
	}

	/**
     * Add a where not between statement to the query.
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereNotBetween($column, array $values, $boolean = 'and') {
        
		return $this->whereBetween($column, $values, $boolean, true);
	}

	/**
     * Add an or where not between statement to the query.
     *
     * @param  string  $column
     * @param  array   $values
     * @return Atom\QueryBuilder|static
     */
	public function orWhereNotBetween($column, array $values) {
        
		return $this->whereNotBetween($column, $values, 'or');
	}

	/**
     * Add a nested where statement to the query.
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereNested(Closure $callback, $boolean = 'and') {
        
		// To handle nested queries we'll actually create a brand new query instance
		// and pass it off to the Closure that we have. The Closure can simply do
		// do whatever it wants to a query then we will store it for compiling.
		$query = $this->newQuery();

		$query->from($this->from);

		call_user_func($callback, $query);

		return $this->addNestedWhereQuery($query, $boolean);
	}

	/**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  Atom\QueryBuilder|static $query
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function addNestedWhereQuery($query, $boolean = 'and') {
        
		if (count($query->wheres)) {
			$type = 'Nested';
			$this->wheres[] = compact('type', 'query', 'boolean');
			$this->mergeBindings($query);
		}

		return $this;
	}

	/**
     * Add a full sub-select to the query.
     *
     * @param  string   $column
     * @param  string   $operator
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return Atom\QueryBuilder|static
     */
	protected function whereSub($column, $operator, Closure $callback, $boolean) {
        
		$type = 'Sub';

		$query = $this->newQuery();

		// Once we have the query instance we can simply execute it so it can add all
		// of the sub-select's conditions to itself, and then we can cache it off
		// in the array of where clauses for the "main" parent query instance.
		call_user_func($callback, $query);

		$this->wheres[] = compact('type', 'column', 'operator', 'query', 'boolean');

		$this->mergeBindings($query);

		return $this;
	}

	/**
     * Add an exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return Atom\QueryBuilder|static
     */
	public function whereExists(Closure $callback, $boolean = 'and', $not = false) {
        
		$type = $not ? 'NotExists' : 'Exists';

		$query = $this->newQuery();

		// Similar to the sub-select clause, we will create a new query instance so
		// the developer may cleanly specify the entire exists query and we will
		// compile the whole thing in the grammar and insert it into the SQL.
		call_user_func($callback, $query);

		$this->wheres[] = compact('type', 'operator', 'query', 'boolean');

		$this->mergeBindings($query);

		return $this;
	}

	/**
     * Add an or exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  bool     $not
     * @return Atom\QueryBuilder|static
     */
	public function orWhereExists(Closure $callback, $not = false) {
        
		return $this->whereExists($callback, 'or', $not);
	}

	/**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereNotExists(Closure $callback, $boolean = 'and') {
        
		return $this->whereExists($callback, $boolean, true);
	}

	/**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure  $callback
     * @return Atom\QueryBuilder|static
     */
	public function orWhereNotExists(Closure $callback) {
        
		return $this->orWhereExists($callback, true);
	}

	/**
     * Add a "where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @param  bool    $not
     * @return Atom\QueryBuilder|static
     */
	public function whereIn($column, $values, $boolean = 'and', $not = false) {
        
		$type = $not ? 'NotIn' : 'In';

		// If the value of the where in clause is actually a Closure, we will assume that
		// the developer is using a full sub-select for this "in" statement, and will
		// execute those Closures, then we can re-construct the entire sub-selects.
		if ($values instanceof Closure) {
			return $this->whereInSub($column, $values, $boolean, $not);
		}

		$this->wheres[] = compact('type', 'column', 'values', 'boolean');

		$this->addBinding($values, 'where');

		return $this;
	}

	/**
     * Add an "or where in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @return Atom\QueryBuilder|static
     */
	public function orWhereIn($column, $values) {

		return $this->whereIn($column, $values, 'or');
	}

	/**
     * Add a "where not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereNotIn($column, $values, $boolean = 'and') {

		return $this->whereIn($column, $values, $boolean, true);
	}

	/**
     * Add an "or where not in" clause to the query.
     *
     * @param  string  $column
     * @param  mixed   $values
     * @return Atom\QueryBuilder|static
     */
	public function orWhereNotIn($column, $values) {

		return $this->whereNotIn($column, $values, 'or');
	}

	/**
     * Add a where in with a sub-select to the query.
     *
     * @param  string   $column
     * @param  \Closure $callback
     * @param  string   $boolean
     * @param  bool     $not
     * @return Atom\QueryBuilder|static
     */
	protected function whereInSub($column, Closure $callback, $boolean, $not) {

		$type = $not ? 'NotInSub' : 'InSub';

		// To create the exists sub-select, we will actually create a query and call the
		// provided callback with the query so the developer may set any of the query
		// conditions they want for the in clause, then we'll put it in this array.
		call_user_func($callback, $query = $this->newQuery());

		$this->wheres[] = compact('type', 'column', 'query', 'boolean');

		$this->mergeBindings($query);

		return $this;
	}

	/**
     * Add a "where null" clause to the query.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @param  bool    $not
     * @return Atom\QueryBuilder|static
     */
	public function whereNull($column, $boolean = 'and', $not = false) {

		$type = $not ? 'NotNull' : 'Null';

		$this->wheres[] = compact('type', 'column', 'boolean');

		return $this;
	}

	/**
     * Add an "or where null" clause to the query.
     *
     * @param  string  $column
     * @return Atom\QueryBuilder|static
     */
	public function orWhereNull($column) {

		return $this->whereNull($column, 'or');
	}

	/**
     * Add a "where not null" clause to the query.
     *
     * @param  string  $column
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereNotNull($column, $boolean = 'and') {

		return $this->whereNull($column, $boolean, true);
	}

	/**
     * Add an "or where not null" clause to the query.
     *
     * @param  string  $column
     * @return Atom\QueryBuilder|static
     */
	public function orWhereNotNull($column) {

		return $this->whereNotNull($column, 'or');
	}

	/**
     * Add a "where day" statement to the query.
     *
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereDay($column, $operator, $value, $boolean = 'and') {

		return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
	}

	/**
     * Add a "where month" statement to the query.
     *
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereMonth($column, $operator, $value, $boolean = 'and') {

		return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
	}

	/**
     * Add a "where year" statement to the query.
     *
     * @param  string  $column
     * @param  string   $operator
     * @param  int   $value
     * @param  string   $boolean
     * @return Atom\QueryBuilder|static
     */
	public function whereYear($column, $operator, $value, $boolean = 'and') {

		return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
	}

	/**
     * Add a date based (year, month, day) statement to the query.
     *
     * @param  string  $type
     * @param  string  $column
     * @param  string  $operator
     * @param  int  $value
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	protected function addDateBasedWhere($type, $column, $operator, $value, $boolean = 'and') {

		$this->wheres[] = compact('column', 'type', 'boolean', 'operator', 'value');

		$this->addBinding($value, 'where');

		return $this;
	}

	/**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string  $method
     * @param  string  $parameters
     * @return Atom\QueryBuilder|static
     */
	public function dynamicWhere($method, $parameters) {

		$finder = substr($method, 5);

		$segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);

		// The connector variable will determine which connector will be used for the
		// query condition. We will change it as we come across new boolean values
		// in the dynamic method strings, which could contain a number of these.
		$connector = 'and';

		$index = 0;

		foreach ($segments as $segment) {

			// If the segment is not a boolean connector, we can assume it is a column's name
			// and we will add it to the query as a new constraint as a where clause, then
			// we can keep iterating through the dynamic method string's segments again.
			if ($segment != 'And' && $segment != 'Or') {
				$this->addDynamic($segment, $connector, $parameters, $index);
				$index++;
            // Otherwise, we will store the connector so we know how the next where clause we
            // find in the query should be connected to the previous ones, meaning we will
            // have the proper boolean connector to connect the next where clause found.
			} else {
				$connector = $segment;
			}
		}

		return $this;
	}

	/**
     * Add a single dynamic where clause statement to the query.
     *
     * @param  string  $segment
     * @param  string  $connector
     * @param  array   $parameters
     * @param  int     $index
     * @return void
     */
	protected function addDynamic($segment, $connector, $parameters, $index) {

		// Once we have parsed out the columns and formatted the boolean operators we
		// are ready to add it to this query as a where clause just like any other
		// clause on the query. Then we'll increment the parameter index values.
		$bool = strtolower($connector);

		$this->where(snake_case($segment), '=', $parameters[$index], $bool);
	}

	/**
     * Add a "group by" clause to the query.
     *
     * @param  dynamic  $columns
     * @return Atom\QueryBuilder|static
     */
	public function groupBy() {

		$this->groups = array_merge((array) $this->groups, func_get_args());

		return $this;
	}

	/**
     * Add a "having" clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function having($column, $operator = null, $value = null, $boolean = 'and') {

		$type = 'basic';

		$this->havings[] = compact('type', 'column', 'operator', 'value', 'boolean');

		$this->addBinding($value, 'having');

		return $this;
	}

	/**
     * Add a "or having" clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  string  $value
     * @return Atom\QueryBuilder|static
     */
    public function orHaving($column, $operator = null, $value = null) {
        
        return $this->having($column, $operator, $value, 'or');
    }

	/**
     * Add a raw having clause to the query.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @param  string  $boolean
     * @return Atom\QueryBuilder|static
     */
	public function havingRaw($sql, array $bindings = array(), $boolean = 'and') {

		$type = 'raw';

		$this->havings[] = compact('type', 'sql', 'boolean');

		$this->addBinding($bindings, 'having');

		return $this;
	}

	/**
     * Add a raw or having clause to the query.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return Atom\QueryBuilder|static
     */
	public function orHavingRaw($sql, array $bindings = array()) {

		return $this->havingRaw($sql, $bindings, 'or');
	}

	/**
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return Atom\QueryBuilder|static
     */
	public function orderBy($column, $direction = 'asc') {

		$direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';

		$this->orders[] = compact('column', 'direction');

		return $this;
	}

	/**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string  $column
     * @return Atom\QueryBuilder|static
     */
	public function latest($column = 'created_at') {

		return $this->orderBy($column, 'desc');
	}

	/**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string  $column
     * @return Atom\QueryBuilder|static
     */
	public function oldest($column = 'created_at') {

		return $this->orderBy($column, 'asc');
	}

	/**
     * Add a raw "order by" clause to the query.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @return Atom\QueryBuilder|static
     */
	public function orderByRaw($sql, $bindings = array()) {

		$type = 'raw';

		$this->orders[] = compact('type', 'sql');

		$this->addBinding($bindings, 'order');

		return $this;
	}

	/**
     * Set the "offset" value of the query.
     *
     * @param  int  $value
     * @return Atom\QueryBuilder|static
     */
	public function offset($value) {

		$this->offset = max(0, $value);

		return $this;
	}

	/**
     * Alias to set the "offset" value of the query.
     *
     * @param  int  $value
     * @return Atom\QueryBuilder|static
     */
	public function skip($value) {

		return $this->offset($value);
	}

	/**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return Atom\QueryBuilder|static
     */
	public function limit($value) {

		if ($value > 0) {
            $this->limit = $value;
        }
        
		return $this;
	}

	/**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return Atom\QueryBuilder|static
     */
	public function take($value) {

		return $this->limit($value);
	}

	/**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return Atom\QueryBuilder|static
     */
	public function forPage($page, $perPage = 15) {

		return $this->skip(($page - 1) * $perPage)->take($perPage);
	}

	/**
     * Add a union statement to the query.
     *
     * @param  Atom\QueryBuilder|\Closure  $query
     * @param  bool $all
     * @return Atom\QueryBuilder|static
     */
	public function union($query, $all = false) {

		if ($query instanceof Closure) {
			call_user_func($query, $query = $this->newQuery());
		}

		$this->unions[] = compact('query', 'all');

		return $this->mergeBindings($query);
	}

	/**
     * Add a union all statement to the query.
     *
     * @param  Atom\QueryBuilder|\Closure  $query
     * @return Atom\QueryBuilder|static
     */
	public function unionAll($query) {

		return $this->union($query, true);
	}

	/**
     * Lock the selected rows in the table.
     *
     * @param  bool  $update
     * @return Atom\QueryBuilder
     */
	public function lock($value = true) {

		$this->lock = $value;

		return $this;
	}

	/**
     * Lock the selected rows in the table for updating.
     *
     * @return Atom\QueryBuilder
     */
	public function lockForUpdate() {

		return $this->lock(true);
	}

	/**
     * Share lock the selected rows in the table.
     *
     * @return Atom\QueryBuilder
     */
	public function sharedLock() {

		return $this->lock(false);
	}

	/**
     * Get the SQL representation of the query.
     *
     * @return string
     */
	public function toSql() {

		return $this->grammar->compileSelect($this);
	}

	/**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return Atom\QueryBuilder|static
     */
	public function remember($minutes, $key = null) {

		list($this->cacheMinutes, $this->cacheKey) = array($minutes, $key);

		return $this;
	}

	/**
     * Indicate that the query results should be cached forever.
     *
     * @param  string  $key
     * @return Atom\QueryBuilder|static
     */
	public function rememberForever($key = null) {

		return $this->remember(-1, $key);
	}

	/**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|dynamic  $cacheTags
     * @return Atom\QueryBuilder|static
     */
	public function cacheTags($cacheTags) {

		$this->cacheTags = $cacheTags;

		return $this;
	}

	/**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param  string  $cacheDriver
     * @return Atom\QueryBuilder|static
     */
	public function cacheDriver($cacheDriver) {

		$this->cacheDriver = $cacheDriver;

		return $this;
	}

	/**
     * Execute a query for a single record by ID.
     *
     * @param  int    $id
     * @param  array  $columns
     * @return mixed|static
     */
	public function find($id, $columns = array('*')) {
		$key = $this->getPrimaryKey();
		return $this->where($key, '=', $id)->first($columns);
	}

	/**
     * Pluck a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
	public function pluck($column) {

		$result = (array) $this->first(array($column));

		return count($result) > 0 ? reset($result) : null;
	}

	/**
     * Execute the query and get the first result.
     *
     * @param  array   $columns
     * @return mixed|static
     */
	public function first($columns = array('*')) {

		$results = $this->take(1)->get($columns);

		return count($results) > 0 ? reset($results) : null;
	}

	/**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
	public function get($columns = array('*')) {

        //if (!is_null($this->cacheMinutes)) return $this->getCached($columns);

        return $this->getFresh($columns);
	}

	/**
     * Execute the query as a fresh "select" statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
	public function getFresh($columns = array('*')) {

        if (is_null($this->columns)) {
            
            $this->columns = $columns;
        }
        
        return $this->runSelect();
	}
    
    /**
     * Prepare the query bindings for execution.
     *
     * @param  array  $bindings
     * @return array
     */
    public function prepareBindings(array $bindings) {

		$grammar = $this->getMySqlGrammar();

		foreach ($bindings as $key => $value) {

			// We need to transform all instances of the DateTime class into an actual
			// date string. Each query grammar maintains its own date string format
			// so we'll just ask the grammar for the format to get from the date.
			if ($value instanceof DateTime) {
				$bindings[$key] = $value->format($grammar->getDateFormat());
			}
			elseif ($value === false) {
				$bindings[$key] = 0;
			}
		}

		return $bindings;
	}
    
    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return PDO constant
     */
    protected function getFetchMode() {

        return $this->fetchMode;
    }

	/**
     * Execute the query as a cached "select" statement.
     *
     * @param  array  $columns
     * @return array
     */
	public function getCached($columns = array('*')) {

        //if (is_null($this->columns)) $this->columns = $columns;

        //// If the query is requested to be cached, we will cache it using a unique key
        //// for this database connection and query statement, including the bindings
        //// that are used on this query, providing great convenience when caching.
        //list($key, $minutes) = $this->getCacheInfo();

        //$cache = $this->getCache();

        //$callback = $this->getCacheCallback($columns);

        //// If the "minutes" value is less than zero, we will use that as the indicator
        //// that the value should be remembered values should be stored indefinitely
        //// and if we have minutes we will use the typical remember function here.
        //if ($minutes < 0)
        //{
        //    return $cache->rememberForever($key, $callback);
        //}
        //else
        //{
        //    return $cache->remember($key, $minutes, $callback);
        //}
	}

	/**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
	protected function getCache() {

        //$cache = $this->connection->getCacheManager()->driver($this->cacheDriver);

        //return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
	}

	/**
     * Get the cache key and cache minutes as an array.
     *
     * @return array
     */
	protected function getCacheInfo() {

		return array($this->getCacheKey(), $this->cacheMinutes);
	}

	/**
     * Get a unique cache key for the complete query.
     *
     * @return string
     */
	public function getCacheKey() {

        //return $this->cacheKey ?: $this->generateCacheKey();
	}

	/**
     * Generate the unique cache key for the query.
     *
     * @return string
     */
	public function generateCacheKey() {

        //$name = $this->connection->getName();
        //return md5($name.$this->toSql().serialize($this->getBindings()));
	}

	/**
     * Get the Closure callback used when caching queries.
     *
     * @param  array  $columns
     * @return \Closure
     */
	protected function getCacheCallback($columns) {

		return function() use ($columns) {
            return $this->getFresh($columns);
        };
	}

	/**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return void
     */
	public function chunk($count, $callback) {

		$results = $this->forPage($page = 1, $count)->get();

		while (count($results) > 0) {

			// On each chunk result set, we will pass them to the callback and then let the
			// developer take care of everything within the callback, which allows us to
			// keep the memory low for spinning through large result sets for working.
			call_user_func($callback, $results);

			$page++;

			$results = $this->forPage($page, $count)->get();
		}
	}

	/**
     * Get an array with the values of a given column.
     *
     * @param  string  $column
     * @param  string  $key
     * @return array
     */
	public function lists($column, $key = null) {

		$columns = $this->getListSelect($column, $key);

		// First we will just get all of the column values for the record result set
		// then we can associate those values with the column if it was specified
		// otherwise we can just give these values back without a specific key.
        
		$results = new Collection($this->get($columns));

		$values = $results->fetch($columns[0])->out();

		// If a key was specified and we have results, we will go ahead and combine
		// the values with the keys of all of the records so that the values can
		// be accessed by the key of the rows instead of simply being numeric.
		if (!is_null($key) && count($results) > 0) {
			$keys = $results->fetch($key)->out();
			return array_combine($keys, $values);
		}

		return $values;
	}

	/**
     * Get the columns that should be used in a list array.
     *
     * @param  string  $column
     * @param  string  $key
     * @return array
     */
	protected function getListSelect($column, $key) {

		$select = is_null($key) ? array($column) : array($column, $key);

		// If the selected column contains a "dot", we will remove it so that the list
		// operation can run normally. Specifying the table is not needed, since we
		// really want the names of the columns as it is in this resulting array.
		if (($dot = strpos($select[0], '.')) !== false) {
			$select[0] = substr($select[0], $dot + 1);
		}

		return $select;
	}

	/**
     * Concatenate values of a given column as a string.
     *
     * @param  string  $column
     * @param  string  $glue
     * @return string
     */
	public function implode($column, $glue = null) {
        
        $imploded = null;
        
		if (is_null($glue)) {
            $imploded = implode($this->lists($column));
        } else {
            $imploded = implode($glue, $this->lists($column));
        }
        
		return $imploded;
	}

	/**
     * Get a paginator for the "select" statement.
     *
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Illuminate\Pagination\Paginator
     */
	/*public function paginate($perPage = 15, $columns = array('*')) {

        $paginator = $this->connection->getPaginator();

        if (isset($this->groups))
        {
            return $this->groupedPaginate($paginator, $perPage, $columns);
        }
        else
        {
            return $this->ungroupedPaginate($paginator, $perPage, $columns);
        }
	}*/

	/**
     * Create a paginator for a grouped pagination statement.
     *
     * @param  \Illuminate\Pagination\Factory  $paginator
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Illuminate\Pagination\Paginator
     */
	/*protected function groupedPaginate($paginator, $perPage, $columns) {

		$results = $this->get($columns);

		return $this->buildRawPaginator($paginator, $results, $perPage);
	}*/

	/**
     * Build a paginator instance from a raw result array.
     *
     * @param  \Illuminate\Pagination\Factory  $paginator
     * @param  array  $results
     * @param  int    $perPage
     * @return \Illuminate\Pagination\Paginator
     */
	/*public function buildRawPaginator($paginator, $results, $perPage) {

		// For queries which have a group by, we will actually retrieve the entire set
		// of rows from the table and "slice" them via PHP. This is inefficient and
		// the developer must be aware of this behavior; however, it's an option.
		$start = ($paginator->getCurrentPage() - 1) * $perPage;

		$sliced = array_slice($results, $start, $perPage);

		return $paginator->make($sliced, count($results), $perPage);
	}*/

	/**
     * Create a paginator for an un-grouped pagination statement.
     *
     * @param  \Illuminate\Pagination\Factory  $paginator
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Illuminate\Pagination\Paginator
     */
	/*protected function ungroupedPaginate($paginator, $perPage, $columns) {

		$total = $this->getPaginationCount();

		// Once we have the total number of records to be paginated, we can grab the
		// current page and the result array. Then we are ready to create a brand
		// new Paginator instances for the results which will create the links.
		$page = $paginator->getCurrentPage($total);

		$results = $this->forPage($page, $perPage)->get($columns);

		return $paginator->make($results, $total, $perPage);
	}*/

	/**
     * Get the count of the total records for pagination.
     *
     * @return int
     */
	public function getPaginationCount() {

		list($orders, $this->orders) = array($this->orders, null);

		$columns = $this->columns;

		// Because some database engines may throw errors if we leave the ordering
		// statements on the query, we will "back them up" and remove them from
		// the query. Once we have the count we will put them back onto this.
		$total = $this->count();

		$this->orders = $orders;

		// Once the query is run we need to put the old select columns back on the
		// instance so that the select query will run properly. Otherwise, they
		// will be cleared, then the query will fire with all of the columns.
		$this->columns = $columns;

		return $total;
	}

	/**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int    $perPage
     * @param  array  $columns
     * @return \Illuminate\Pagination\Paginator
     */
	/*public function simplePaginate($perPage = null, $columns = array('*')) {

        $paginator = $this->connection->getPaginator();

        $page = $paginator->getCurrentPage();

        $perPage = $perPage ?: $this->model->getPerPage();

        $this->skip(($page - 1) * $perPage)->take($perPage + 1);

        return $paginator->make($this->get($columns), $perPage);
	}*/

	/**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
	public function exists() {

		return $this->count() > 0;
	}

	/**
     * Retrieve the "count" result of the query.
     *
     * @param  string  $column
     * @return int
     */
	public function count($column = '*') {

		return (int) $this->aggregate(__FUNCTION__, array($column));
	}

	/**
     * Retrieve the minimum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
	public function min($column) {

		return $this->aggregate(__FUNCTION__, array($column));
	}

	/**
     * Retrieve the maximum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
	public function max($column) {

		return $this->aggregate(__FUNCTION__, array($column));
	}

	/**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
	public function sum($column) {

		$result = $this->aggregate(__FUNCTION__, array($column));

		return $result ?: 0;
	}

	/**
     * Retrieve the average of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
	public function avg($column) {

		return $this->aggregate(__FUNCTION__, array($column));
	}

	/**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  array   $columns
     * @return mixed
     */
	public function aggregate($function, $columns = array('*')) {

		$this->aggregate = compact('function', 'columns');

		$results = $this->get($columns);

		// Once we have executed the query, we will reset the aggregate property so
		// that more select queries can be executed against the database without
		// the aggregate value getting in the way when the grammar builds it.
		$this->columns = null; $this->aggregate = null;

		if (isset($results[0])) {
			$result = array_change_key_case((array) $results[0]);
			return $result['aggregate'];
		}
	}

	/**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
	public function insert(array $values) {

		// Since every insert gets treated like a batch insert, we will make sure the
		// bindings are structured in a way that is convenient for building these
		// inserts statements by verifying the elements are actually an array.
		if (!is_array(reset($values))) {
			$values = array($values);

        // Since every insert gets treated like a batch insert, we will make sure the
        // bindings are structured in a way that is convenient for building these
        // inserts statements by verifying the elements are actually an array.
        } else {
			foreach ($values as $key => $value) {
				ksort($value); $values[$key] = $value;
			}
		}

		// We'll treat every insert like a batch insert so we can easily insert each
		// of the records into the database consistently. This will make it much
		// easier on the grammars to just handle one type of record insertion.
		$bindings = array();

		foreach ($values as $record) {
			$bindings = array_merge($bindings, array_values($record));
		}

		$sql = $this->grammar->compileInsert($this, $values);

		// Once we have compiled the insert statement's SQL we can execute it on the
		// connection and return a result as a boolean success indicator as that
		// is the same type of result returned by the raw connection instance.
		$bindings = $this->cleanBindings($bindings);
		
		return $this->runStatement($sql, $bindings);
	}

	/**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
	public function insertGetId(array $values, $sequence = null) {

		$sql = $this->grammar->compileInsertGetId($this, $values, $sequence);

		$values = $this->cleanBindings($values);
		
		$this->runStatement($sql, $values);
		
		$id = $this->getPdo()->lastInsertId($sequence);
		
		return is_numeric($id) ? (int) $id : $id;
	}

	/**
     * Update a record in the database.
     *
     * @param  array  $values
     * @return int
     */
	public function update(array $values) {

		$bindings = array_values(array_merge($values, $this->getBindings()));

		$sql = $this->grammar->compileUpdate($this, $values);
        
        return $this->runStatementWithRows($sql, $this->cleanBindings($bindings));
	}

	/**
     * Increment a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int     $amount
     * @param  array   $extra
     * @return int
     */
	public function increment($column, $amount = 1, array $extra = array()) {

		$wrapped = $this->grammar->wrap($column);

		$columns = array_merge(array($column => $this->raw("$wrapped + $amount")), $extra);

		return $this->update($columns);
	}

	/**
     * Decrement a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int     $amount
     * @param  array   $extra
     * @return int
     */
	public function decrement($column, $amount = 1, array $extra = array()) {

		$wrapped = $this->grammar->wrap($column);

		$columns = array_merge(array($column => $this->raw("$wrapped - $amount")), $extra);

		return $this->update($columns);
	}

	/**
     * Delete a record from the database.
     *
     * @param  mixed  $id
     * @return int
     */
	public function delete($id = null) {

		// If an ID is passed to the method, we will set the where clause to check
		// the ID to allow developers to simply and quickly remove a single row
		// from their database without manually specifying the where clauses.
		if (!is_null($id)) {
			$key = $this->getPrimaryKey();
            $this->where($key, '=', $id);
        }
		
		$sql = $this->grammar->compileDelete($this);
        
        return $this->runStatementWithRows($sql, $this->getBindings());
	}
	
	public function getPrimaryKey() {
		
		if(!$this->primaryKey) {
			
			$from = new Expression('INFORMATION_SCHEMA.COLUMNS');
			$column = 'COLUMN_NAME';
			$table = str_ireplace('`','',$this->grammar->wrapTable($this->from));
			$qb = $this->newQuery();
			$pk = $qb->select([$column])
				->from($from)
				->where('TABLE_NAME', '=',$table)
				->where('COLUMN_KEY', '=','PRI')
				->where('EXTRA','=','auto_increment')
				->runSelect();
				
			if(count($pk) && array_key_exists($column,$pk[0])) {
				$this->primaryKey = $pk[0][$column];
			} else {
				$this->primaryKey = 'id';
			}
		}
		
		return $this->primaryKey;
	}

	/**
     * Run a truncate statement on the table.
     *
     * @return void
     */
	public function truncate() {

		foreach ($this->grammar->compileTruncate($this) as $sql => $bindings) {
            $this->runStatement($sql, $bindings);
		}
	}

	/**
     * Get a new instance of the query builder.
     *
     * @return Atom\QueryBuilder
     */
	public function newQuery() {

		return new static;
	}

	/**
     * Merge an array of where clauses and bindings.
     *
     * @param  array  $wheres
     * @param  array  $bindings
     * @return void
     */
	public function mergeWheres($wheres, $bindings) {

		$this->wheres = array_merge((array) $this->wheres, (array) $wheres);

		$this->bindings['where'] = array_values(array_merge($this->bindings['where'], (array) $bindings));
	}

	/**
     * Remove all of the expressions from a list of bindings.
     *
     * @param  array  $bindings
     * @return array
     */
	protected function cleanBindings(array $bindings) {

		return array_values(array_filter($bindings, function($binding) {
			return ! $binding instanceof Expression;
		}));
	}

	/**
     * Create a raw database expression.
     *
     * @param  mixed  $value
     * @return Atom\Expression
     */
	public function raw($value) {

        return new Expression($value);
	}

	/**
     * Get the current query value bindings in a flattened array.
     *
     * @return array
     */
	public function getBindings() {

        return (new Collection($this->bindings))->level()->out();
	}

	/**
     * Get the raw array of bindings.
     *
     * @return array
     */
	public function getRawBindings() {

		return $this->bindings;
	}

	/**
     * Set the bindings on the query builder.
     *
     * @param  array   $bindings
     * @param  string  $type
     * @return Atom\QueryBuilder
     */
	public function setBindings(array $bindings, $type = 'where') {

		if (!array_key_exists($type, $this->bindings)) {
			throw new \InvalidArgumentException("Invalid binding type: {$type}.");
		}

		$this->bindings[$type] = $bindings;

		return $this;
	}

	/**
     * Add a binding to the query.
     *
     * @param  mixed   $value
     * @param  string  $type
     * @return Atom\QueryBuilder
     */
	public function addBinding($value, $type = 'where') {

		if (!array_key_exists($type, $this->bindings)) {
            throw new \InvalidArgumentException("Invalid binding type: {$type}.");
		}

		if (is_array($value)) {
			$this->bindings[$type] = array_values(array_merge($this->bindings[$type], $value));
		} else {

			$this->bindings[$type][] = $value;
		}

		return $this;
	}

	/**
     * Merge an array of bindings into our bindings.
     *
     * @param  Atom\QueryBuilder  $query
     * @return Atom\QueryBuilder
     */
	public function mergeBindings(QueryBuilder $query) {

		$this->bindings = array_merge_recursive($this->bindings, $query->bindings);

		return $this;
	}

	/**
     * Get the query grammar instance.
     *
     * @return \Illuminate\Database\MySqlGrammar
     */
	public function getMySqlGrammar() {

		return $this->grammar;
	}

	/**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    /*public function __call($method, $parameters)
    {
        if (starts_with($method, 'where'))
        {
            return $this->dynamicWhere($method, $parameters);
        }

        $className = get_class($this);

        throw new \BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }*/
    
    protected function getPdo() {
        
		if(!static::$pdo) {
			$server = Config::get('database','server');
			$database = Config::get('database','database');
			$username = Config::get('database','user');
			$password = Config::get('database','password');
			$dsn = 'mysql:host='.$server.';dbname='.$database;
			
			static::$pdo = new PDO($dsn, $username, $password);
			
			static::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		
		return static::$pdo;
    }
    
    protected function run($query, $bindings, Closure $callback) {

		$start = microtime(true);

		// To execute the statement, we'll simply call the callback, which will actually
		// run the SQL against the PDO connection. Then we can calculate the time it
		// took to execute and log the query SQL, bindings and time in our memory.
		try {
			$result = $callback($this, $query, $bindings);
		
		// If an exception occurs when attempting to run a query, we'll format the error
		// message to include the bindings with SQL, which will make this exception a
		// lot more helpful to the developer instead of just the database's errors.
		} catch (Exception $e) {
			throw new QueryException($query, $bindings, $e);
		}

		// Once we have run the query we will calculate the time that it took to run and
		// then log the query, bindings, and execution time so we will report them on
		// the event that the developer needs them. We'll log time in milliseconds.
        if($this->logging === true) {
            $time = $this->getElapsedTime($start);
            $this->logQuery($query, $bindings, $time);
        }
        
		return $result;
	}
    
    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
	protected function runSelect() {

        return $this->run($this->toSql(), $this->getBindings(), function($me, $query, $bindings){
            
			// For select statements, we'll simply execute the query and return an array
			// of the database result set. Each element in the array will be a single
			// row from the database table, and will either be an array or objects.
			$pdo = $me->getPdo();
			
            $bindings = $me->prepareBindings($bindings);
            
			$statement = $pdo->prepare($query);
            
			$statement->execute($bindings);
			
			$results = $statement->fetchAll($me->getFetchMode());
			
			return $results;
        });
	}
    
    protected function runStatement($query, $bindings = array()) {

		return $this->run($query, $bindings, function($me, $query, $bindings) {
			
			$pdo = $me->getPdo();
            
            $bindings = $me->prepareBindings($bindings);
            
			$statement = $pdo->prepare($query);
			
			$results = $statement->execute($bindings);
			
			return $results;
		});
	}

	/**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
	protected function runStatementWithRows($query, $bindings = array()) {

		return $this->run($query, $bindings, function($me, $query, $bindings) {

			// For update or delete statements, we want to get the number of rows affected
			// by the statement and return that back to the developer. We'll first need
			// to execute the statement and then we'll use PDO to fetch the affected.
			$pdo = $me->getPdo();
			
            $bindings = $me->prepareBindings($bindings);
            
			$statement = $pdo->prepare($query);

			$statement->execute($bindings);
			
			$results = $statement->rowCount();
			
			return $results;
		});
	}
	
	public function runRaw(Expression $expression, $params = array(), $type = 'fetchAll') {
		
		if($expression instanceof Expression) {
			
			$sql = $expression->getValue();
			
			$method = 'run'.ucfirst($type);
			
			return $this->{$method}($sql, $params);
		}
	}
	
	protected function runFetchAll($query, $params = array()) {

        return $this->run($query, $params, function($me, $query, $bindings) {
			
			$pdo = $me->getPdo();
            
            $bindings = $me->prepareBindings($bindings);
            
			$statement = $pdo->prepare($query);
			
			if(count($bindings)) {
				$statement->execute($bindings);
			} else {
				$statement->execute();
			}
			
			$results = $statement->fetchAll($me->getFetchMode());
			
			return $results;
		});
	}
    
    protected function getElapsedTime($start) {

        return round((microtime(true) - $start) * 1000, 2);
    }
    
    public function logQuery($query, $bindings, $time = null) {

        //if (isset($this->events))
        //{
        //    $this->events->fire('illuminate.query', array($query, $bindings, $time, $this->getName()));
        //}

        //if (!$this->loggingQueries) return;

        //$this->queryLog[] = compact('query', 'bindings', 'time');
	}

}
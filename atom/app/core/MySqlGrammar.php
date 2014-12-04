<?php

/**
 * AtomMVC: MySqlGrammar Class
 * atom/app/lib/MySqlGrammar.php
 *
 * @copyright Copyright (c) 2014, Jason Dielman
 * @author Jason Dielman <jhdielman@gmail.com>
 *
 * @see https://github.com/illuminate/database/blob/master/Query/Grammars/MySqlGrammar.php
 *
 */

namespace Atom;

class MySqlGrammar {
    
    /**
     * The grammar date format.
     *
     * @constant string
     */
    const DateFormat = 'Y-m-d H:i:s';
    
    /**
     * The grammar table prefix.
     *
     * @var string
     */
    protected $tablePrefix = '';
    /**
     * The keyword identifier wrapper format.
     *
     * @var string
     */
    protected $wrapper = '`%s`';

    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = array(
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'unions',
        'lock',
    );

    public function __construct() {
        $this->tablePrefix = Config::get('database','tablePrefix');
    }

    /**
     * Compile a select query into SQL.
     *
     * @param  Atom\QueryBuilder
     * @return string
     */
    public function compileSelect(QueryBuilder $query) {

        if (is_null($query->columns)) $query->columns = array('*');

        $sql = trim($this->concatenate($this->compileComponents($query)));

        if ($query->unions) {
            $sql = '('.$sql.') '.$this->compileUnions($query);
        }

        return $sql;
    }

    /**
     * Compile the components necessary for a select clause.
     *
     * @param  Atom\QueryBuilder
     * @return array
     */
    protected function compileComponents(QueryBuilder $query) {

        $sql = array();

        foreach ($this->selectComponents as $component) {

            // To compile the query, we'll spin through each component of the query and
            // see if that component exists. If it does we'll just call the compiler
            // function for the component which is responsible for making the SQL.
            if(!is_null($query->$component)) {

                $method = 'compile'.ucfirst($component);

                $sql[$component] = $this->$method($query, $query->$component);
            }
        }

        return $sql;
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $aggregate
     * @return string
     */
    protected function compileAggregate(QueryBuilder $query, $aggregate) {

        $column = $this->columnize($aggregate['columns']);

        // If the query has a "distinct" constraint and we're not asking for all columns
        // we need to prepend "distinct" onto the column name so that the query takes
        // it into account when it performs the aggregating operations on the data.
        if ($query->distinct && $column !== '*') {

            $column = 'distinct '.$column;
        }

        return 'select '.$aggregate['function'].'('.$column.') as aggregate';
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $columns
     * @return string
     */
    protected function compileColumns(QueryBuilder $query, $columns) {

        // If the query is actually performing an aggregating select, we will let that
        // compiler handle the building of the select clauses, as it will need some
        // more syntax that is best handled by that function to keep things neat.
        if(!is_null($query->aggregate)) return;

        $select = $query->distinct ? 'select distinct ' : 'select ';

        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "from" portion of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  string  $table
     * @return string
     */
    protected function compileFrom(QueryBuilder $query, $table) {

        return 'from '.$this->wrapTable($table);
    }

    /**
     * Compile the "join" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $joins
     * @return string
     */
    protected function compileJoins(QueryBuilder $query, $joins) {

        $sql = array();

        foreach ($joins as $join) {

            $table = $this->wrapTable($join->table);

            // First we need to build all of the "on" clauses for the join. There may be many
            // of these clauses so we will need to iterate through each one and build them
            // separately, then we'll join them up into a single string when we're done.
            $clauses = array();

            foreach ($join->clauses as $clause) {

                $clauses[] = $this->compileJoinConstraint($clause);
            }

            // Once we have constructed the clauses, we'll need to take the boolean connector
            // off of the first clause as it obviously will not be required on that clause
            // because it leads the rest of the clauses, thus not requiring any boolean.
            $clauses[0] = $this->removeLeadingBoolean($clauses[0]);

            $clauses = implode(' ', $clauses);

            $type = $join->type;

            // Once we have everything ready to go, we will just concatenate all the parts to
            // build the final join statement SQL for the query and we can then return the
            // final clause back to the callers as a single, stringified join statement.
            $sql[] = "$type join $table on $clauses";
        }

        return implode(' ', $sql);
    }

    /**
     * Create a join clause constraint segment.
     *
     * @param  array   $clause
     * @return string
     */
    protected function compileJoinConstraint(array $clause, $useBoolean = true) {
        $type = $clause['type'];
        
        if ($type == 'Nested') {
            return $this->compileNestedJoinConstraint($clause);
        }
        else {
            $first = $this->wrap($clause['first']);
            $secondRaw = $clause['second'];
            $operator = $clause['operator'];
            $constraint = "";
            
            if ($useBoolean) {
                $constraint .= "{$clause['boolean']} ";
            }
    
            if (($operator == '=' || $operator == '!=') && $secondRaw == null) {
                if ($operator == '=') {
                    $constraint .= "$first is null";
                }
                else {
                    $constraint .= "$first is not null";
                }
            }
            else {
                $second = ($clause['where'] ? '?' : $this->wrap($secondRaw));
                $constraint .= "$first $operator $second";
            }
            
            return $constraint;
        }
    }

    /**
     * Create a nested join clause constraint segment.
     *
     * @param  array   $clause
     * @return string
     */
    protected function compileNestedJoinConstraint(array $clause) {
        $constraint = "{$clause['boolean']} (";
        $firstClause = true;
        
        foreach ($clause['clauses'] as $innerClause) {
            if (!$firstClause) {
                $constraint .= " ";    
            }
            
            $constraint .= $this->compileJoinConstraint($innerClause, !$firstClause);
            $firstClause = false;
        }
        
        $constraint .= ')';
        
        return $constraint;
    }

    /**
     * Compile the "where" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @return string
     */
    protected function compileWheres(QueryBuilder $query) {

        $sql = array();

        if (is_null($query->wheres)) return '';

        // Each type of where clauses has its own compiler function which is responsible
        // for actually creating the where clauses SQL. This helps keep the code nice
        // and maintainable since each clause has a very small method that it uses.
        foreach ($query->wheres as $where) {

            $method = "where{$where['type']}";

            $sql[] = $where['boolean'].' '.$this->$method($query, $where);
        }

        // If we actually have some where clauses, we will strip off the first boolean
        // operator, which is added by the query builders for convenience so we can
        // avoid checking for the first clauses in each of the compilers methods.
        if (count($sql) > 0) {

            $sql = implode(' ', $sql);

            return 'where '.preg_replace('/and |or /', '', $sql, 1);
        }

        return '';
    }

    /**
     * Compile a nested where clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNested(QueryBuilder $query, $where) {

        $nested = $where['query'];

        return '('.substr($this->compileWheres($nested), 6).')';
    }

    /**
     * Compile a where condition with a sub-select.
     *
     * @param  Atom\QueryBuilder $query
     * @param  array   $where
     * @return string
     */
    protected function whereSub(QueryBuilder $query, $where) {

        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']).' '.$where['operator']." ($select)";
    }

    /**
     * Compile a basic where clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(QueryBuilder $query, $where) {

        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Compile a "between" where clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBetween(QueryBuilder $query, $where) {

        $between = $where['not'] ? 'not between' : 'between';

        return $this->wrap($where['column']).' '.$between.' ? and ?';
    }

    /**
     * Compile a where exists clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereExists(QueryBuilder $query, $where) {

        return 'exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a where exists clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotExists(QueryBuilder $query, $where) {

        return 'not exists ('.$this->compileSelect($where['query']).')';
    }

    /**
     * Compile a "where in" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereIn(QueryBuilder $query, $where) {

        $values = $this->parameterize($where['values']);

        return $this->wrap($where['column']).' in ('.$values.')';
    }

    /**
     * Compile a "where not in" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotIn(QueryBuilder $query, $where) {

        $values = $this->parameterize($where['values']);

        return $this->wrap($where['column']).' not in ('.$values.')';
    }

    /**
     * Compile a where in sub-select clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereInSub(QueryBuilder $query, $where) {

        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']).' in ('.$select.')';
    }

    /**
     * Compile a where not in sub-select clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotInSub(QueryBuilder $query, $where) {

        $select = $this->compileSelect($where['query']);

        return $this->wrap($where['column']).' not in ('.$select.')';
    }

    /**
     * Compile a "where null" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNull(QueryBuilder $query, $where) {

        return $this->wrap($where['column']).' is null';
    }

    /**
     * Compile a "where not null" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereNotNull(QueryBuilder $query, $where) {

        return $this->wrap($where['column']).' is not null';
    }

    /**
     * Compile a "where day" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereDay(QueryBuilder $query, $where) {

        return $this->dateBasedWhere('day', $query, $where);
    }

    /**
     * Compile a "where month" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereMonth(QueryBuilder $query, $where) {

        return $this->dateBasedWhere('month', $query, $where);
    }

    /**
     * Compile a "where year" clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereYear(QueryBuilder $query, $where) {

        return $this->dateBasedWhere('year', $query, $where);
    }

    /**
     * Compile a date based where clause.
     *
     * @param  string  $type
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function dateBasedWhere($type, QueryBuilder $query, $where) {

        $value = $this->parameter($where['value']);

        return $type.'('.$this->wrap($where['column']).') '.$where['operator'].' '.$value;
    }

    /**
     * Compile a raw where clause.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereRaw(QueryBuilder $query, $where) {

        return $where['sql'];
    }

    /**
     * Compile the "group by" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $groups
     * @return string
     */
    protected function compileGroups(QueryBuilder $query, $groups) {

        return 'group by '.$this->columnize($groups);
    }

    /**
     * Compile the "having" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $havings
     * @return string
     */
    protected function compileHavings(QueryBuilder $query, $havings) {

        $sql = implode(' ', array_map(array($this, 'compileHaving'), $havings));

        return 'having '.preg_replace('/and /', '', $sql, 1);
    }

    /**
     * Compile a single having clause.
     *
     * @param  array   $having
     * @return string
     */
    protected function compileHaving(array $having) {

        // If the having clause is "raw", we can just return the clause straight away
        // without doing any more processing on it. Otherwise, we will compile the
        // clause into SQL based on the components that make it up from builder.
        if ($having['type'] === 'raw') {

            return $having['boolean'].' '.$having['sql'];
        }

        return $this->compileBasicHaving($having);
    }

    /**
     * Compile a basic having clause.
     *
     * @param  array   $having
     * @return string
     */
    protected function compileBasicHaving($having) {

        $column = $this->wrap($having['column']);

        $parameter = $this->parameter($having['value']);

        return $having['boolean'].' '.$column.' '.$having['operator'].' '.$parameter;
    }

    /**
     * Compile the "order by" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $orders
     * @return string
     */
    protected function compileOrders(QueryBuilder $query, $orders) {

        return 'order by '.implode(', ', array_map(function($order) {

            if (isset($order['sql'])) return $order['sql'];

            return $this->wrap($order['column']).' '.$order['direction'];
        }
        , $orders));
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  int  $limit
     * @return string
     */
    protected function compileLimit(QueryBuilder $query, $limit) {

        return 'limit '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  int  $offset
     * @return string
     */
    protected function compileOffset(QueryBuilder $query, $offset) {

        return 'offset '.(int) $offset;
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param  Atom\QueryBuilder  $query
     * @return string
     */
    protected function compileUnions(QueryBuilder $query) {

        $sql = '';

        foreach ($query->unions as $union) {

            $sql .= $this->compileUnion($union);
        }

        return ltrim($sql);
    }

    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union) {

        $joiner = $union['all'] ? ' union all ' : ' union ';

        return $joiner.'('.$union['query']->toSql().')';
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $values
     * @return string
     */
    public function compileInsert(QueryBuilder $query, array $values) {

        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the SQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if(!is_array(reset($values))) {

            $values = array($values);
        }

        $columns = $this->columnize(array_keys(reset($values)));

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we can just go off the first list of values in this array.
        $parameters = $this->parameterize(reset($values));

        $value = array_fill(0, count($values), "($parameters)");

        $parameters = implode(', ', $value);

        return "insert into $table ($columns) values $parameters";
    }

    /**
     * Compile an insert and get ID statement into SQL.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array   $values
     * @param  string  $sequence
     * @return string
     */
    public function compileInsertGetId(QueryBuilder $query, $values, $sequence) {

        return $this->compileInsert($query, $values);
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(QueryBuilder $query, $values) {

        $table = $this->wrapTable($query->from);

        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        $columns = array();

        foreach ($values as $key => $value) {

            $columns[] = $this->wrap($key).' = '.$this->parameter($value);
        }

        $columns = implode(', ', $columns);

        // If the query has any "join" clauses, we will setup the joins on the builder
        // and compile them so we can attach them to this update, as update queries
        // can get join statements to attach to other tables when they're needed.
        if (isset($query->joins)) {

            $joins = ' '.$this->compileJoins($query, $query->joins);
        }
        else
        {
            $joins = '';
        }

        // Of course, update queries may also be constrained by where clauses so we'll
        // need to compile the where clauses and attach it to the query so only the
        // intended records are updated by the SQL statements we generate to run.
        $where = $this->compileWheres($query);

        $sql = trim("update {$table}{$joins} set $columns $where");

        if (isset($query->orders)) {

            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {

            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return rtrim($sql);
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  Atom\QueryBuilder  $query
     * @return string
     */
    public function compileDelete(QueryBuilder $query) {

        $table = $this->wrapTable($query->from);

        $where = is_array($query->wheres) ? $this->compileWheres($query) : '';

        return trim("delete from $table ".$where);
    }

    /**
     * Compile a truncate table statement into SQL.
     *
     * @param  Atom\QueryBuilder  $query
     * @return array
     */
    public function compileTruncate(QueryBuilder $query) {

        return array('truncate '.$this->wrapTable($query->from) => array());
    }

    /**
     * Compile the lock into SQL.
     *
     * @param  Atom\QueryBuilder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(QueryBuilder $query, $value) {

        if (is_string($value)) return $value;

        return $value ? 'for update' : 'lock in share mode';
    }

    /**
     * Concatenate an array of segments, removing empties.
     *
     * @param  array   $segments
     * @return string
     */
    protected function concatenate($segments) {

        return implode(' ', array_filter($segments, function($value) {

            return (string) $value !== '';
        }));
    }

    /**
     * Remove the leading boolean from a statement.
     *
     * @param  string  $value
     * @return string
     */
    protected function removeLeadingBoolean($value) {

        return preg_replace('/and |or /', '', $value, 1);
    }

    /**
     * Wrap an array of values.
     *
     * @param  array  $values
     * @return array
     */
    public function wrapArray(array $values) {

        return array_map(array($this, 'wrap'), $values);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  string  $table
     * @return string
     */
    public function wrapTable($table) {

        if ($this->isExpression($table)) return $this->getValue($table);

        return $this->wrap($this->tablePrefix.$table);
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    public function wrap($value) {

        if ($this->isExpression($value)) return $this->getValue($value);

        // If the value being wrapped has a column alias we will need to separate out
        // the pieces so we can wrap each of the segments of the expression on it
        // own, and then joins them both back together with the "as" connector.
        if (strpos(strtolower($value), ' as ') !== false) {

            $segments = explode(' ', $value);

            return $this->wrap($segments[0]).' as '.$this->wrap($segments[2]);
        }

        $wrapped = array();

        $segments = explode('.', $value);

        // If the value is not an aliased table expression, we'll just wrap it like
        // normal, so if there is more than one segment, we will wrap the first
        // segments as if it was a table and the rest as just regular values.
        foreach ($segments as $key => $segment) {

            if ($key == 0 && count($segments) > 1) {

                $wrapped[] = $this->wrapTable($segment);
            }
            else
            {
                $wrapped[] = $this->wrapValue($segment);
            }
        }

        return implode('.', $wrapped);
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value) {

        return $value !== '*' ? sprintf($this->wrapper, $value) : $value;
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns) {

        return implode(', ', array_map(array($this, 'wrap'), $columns));
    }

    /**
     * Create query parameter place-holders for an array.
     *
     * @param  array   $values
     * @return string
     */
    public function parameterize(array $values) {

        return implode(', ', array_map(array($this, 'parameter'), $values));
    }

    /**
     * Get the appropriate query parameter place-holder for a value.
     *
     * @param  mixed   $value
     * @return string
     */
    public function parameter($value) {

        return $this->isExpression($value) ? $this->getValue($value) : '?';
    }

    /**
     * Get the value of a raw expression.
     *
     * @param  Atom\Expression  $expression
     * @return string
     */
    public function getValue($expression) {

        return $expression->getValue();
    }

    /**
     * Determine if the given value is a raw expression.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpression($value) {

        return $value instanceof Expression;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat() {

        return static::DateFormat;
    }

    /**
     * Get the grammar's table prefix.
     *
     * @return string
     */
    public function getTablePrefix() {

        return $this->tablePrefix;
    }

    /**
     * Set the grammar's table prefix.
     *
     * @param  string  $prefix
     * @return \Illuminate\Database\MySqlGrammar
     */
    public function setTablePrefix($prefix) {

        $this->tablePrefix = $prefix;

        return $this;
    }
}
<?php

namespace Nexa\Database;

use PDO;

class QueryBuilder
{
    protected $model;
    protected $connection;
    protected $table;
    protected $wheres = [];
    protected $orders = [];
    protected $limitValue;
    protected $offsetValue;
    protected $joins = [];
    protected $selects = ['*'];
    protected $bindings = [];

    public function __construct(Model $model = null, PDO $connection = null, $table = null)
    {
        $this->model = $model;
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Ajoute une clause WHERE
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where($column, $operator = null, $value = null)
    {
        // Handle the case where only column and value are provided
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Ajoute une clause WHERE OR
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'or'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Ajoute une clause WHERE IN
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn($column, array $values)
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Ajoute une clause WHERE NOT IN
     *
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereNotIn($column, array $values)
    {
        $this->wheres[] = [
            'type' => 'not_in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Ajoute une clause WHERE NULL
     *
     * @param string $column
     * @return $this
     */
    public function whereNull($column)
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'and'
        ];

        return $this;
    }

    /**
     * Ajoute une clause WHERE NOT NULL
     *
     * @param string $column
     * @return $this
     */
    public function whereNotNull($column)
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => $column,
            'boolean' => 'and'
        ];

        return $this;
    }

    /**
     * Ajoute une clause ORDER BY
     *
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->orders[] = [
            'column' => $column,
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc'
        ];

        return $this;
    }

    /**
     * Ajoute une clause LIMIT
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limitValue = $limit;
        return $this;
    }

    /**
     * Ajoute une clause OFFSET
     *
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offsetValue = $offset;
        return $this;
    }

    /**
     * Ajoute une clause JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'inner')
    {
        if (func_num_args() === 3) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * Ajoute une clause LEFT JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'left');
    }

    /**
     * Ajoute une clause RIGHT JOIN
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return $this
     */
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'right');
    }

    /**
     * Sélectionne des colonnes spécifiques
     *
     * @param array|string $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Exécute la requête et retourne tous les résultats
     *
     * @return array
     */
    public function get()
    {
        $sql = $this->toSql();
        $connection = $this->model ? $this->model->getConnection() : $this->connection;
        $stmt = $connection->prepare($sql);
        $stmt->execute($this->bindings);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->model) {
                $model = new (get_class($this->model));
                $results[] = $model->fill($row);
            } else {
                $results[] = $row;
            }
        }

        return $results;
    }

    /**
     * Exécute la requête et retourne le premier résultat
     *
     * @return Model|null
     */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Compte le nombre de résultats
     *
     * @return int
     */
    public function count()
    {
        $originalSelects = $this->selects;
        $this->selects = ['COUNT(*) as count'];
        
        $sql = $this->toSql();
        $connection = $this->model ? $this->model->getConnection() : $this->connection;
        $stmt = $connection->prepare($sql);
        $stmt->execute($this->bindings);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->selects = $originalSelects;
        
        return (int) $result['count'];
    }

    /**
     * Génère la requête SQL
     *
     * @return string
     */
    public function toSql()
    {
        $sql = 'SELECT ';
        
        if (isset($this->distinct) && $this->distinct) {
            $sql .= 'DISTINCT ';
        }
        
        $sql .= implode(', ', $this->selects);
        $sql .= ' FROM ' . ($this->model ? $this->model->getTableName() : $this->table);

        // Ajouter les JOINs
        foreach ($this->joins as $join) {
            $sql .= ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'];
            $sql .= ' ON ' . $join['first'] . ' ' . $join['operator'] . ' ' . $join['second'];
        }

        // Ajouter les WHEREs
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }

        // Ajouter GROUP BY
        if (isset($this->groups) && !empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }

        // Ajouter HAVING
        if (isset($this->havings) && !empty($this->havings)) {
            $sql .= ' HAVING ' . $this->compileHavings();
        }

        // Ajouter les ORDER BYs
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ';
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = $order['column'] . ' ' . strtoupper($order['direction']);
            }
            $sql .= implode(', ', $orderClauses);
        }

        // Ajouter LIMIT et OFFSET
        if ($this->limitValue !== null) {
            $sql .= ' LIMIT ' . $this->limitValue;
        }

        if ($this->offsetValue !== null) {
            $sql .= ' OFFSET ' . $this->offsetValue;
        }

        // Ajouter UNION
        if (isset($this->unions)) {
            foreach ($this->unions as $union) {
                $sql .= ' UNION';
                if ($union['all']) {
                    $sql .= ' ALL';
                }
                $sql .= ' ' . $union['query'];
            }
        }

        return $sql;
    }

    /**
     * Compile les clauses WHERE
     *
     * @return string
     */
    protected function compileWheres()
    {
        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $boolean = $index === 0 ? '' : strtoupper($where['boolean']) . ' ';

            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $boolean . $where['column'] . ' ' . $where['operator'] . ' ?';
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . $where['column'] . ' IN (' . $placeholders . ')';
                    break;
                case 'not_in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . $where['column'] . ' NOT IN (' . $placeholders . ')';
                    break;
                case 'null':
                    $clauses[] = $boolean . $where['column'] . ' IS NULL';
                    break;
                case 'not_null':
                    $clauses[] = $boolean . $where['column'] . ' IS NOT NULL';
                    break;
                case 'between':
                    $clauses[] = $boolean . $where['column'] . ' BETWEEN ? AND ?';
                    break;
                case 'not_between':
                    $clauses[] = $boolean . $where['column'] . ' NOT BETWEEN ? AND ?';
                    break;
                case 'like':
                    $clauses[] = $boolean . $where['column'] . ' LIKE ?';
                    break;
                case 'not_like':
                    $clauses[] = $boolean . $where['column'] . ' NOT LIKE ?';
                    break;
                case 'raw':
                    $clauses[] = $boolean . $where['sql'];
                    break;
                case 'exists':
                    $clauses[] = $boolean . 'EXISTS (' . $where['query'] . ')';
                    break;
                case 'not_exists':
                    $clauses[] = $boolean . 'NOT EXISTS (' . $where['query'] . ')';
                    break;
            }
        }

        return implode(' ', $clauses);
    }

    // Advanced Query Methods

    /**
     * Add a WHERE BETWEEN clause
     */
    public function whereBetween($column, array $values)
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add a WHERE NOT BETWEEN clause
     */
    public function whereNotBetween($column, array $values)
    {
        $this->wheres[] = [
            'type' => 'not_between',
            'column' => $column,
            'values' => $values,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Add a WHERE LIKE clause
     */
    public function whereLike($column, $value)
    {
        $this->wheres[] = [
            'type' => 'like',
            'column' => $column,
            'value' => $value,
            'boolean' => 'and'
        ];

        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add a WHERE NOT LIKE clause
     */
    public function whereNotLike($column, $value)
    {
        $this->wheres[] = [
            'type' => 'not_like',
            'column' => $column,
            'value' => $value,
            'boolean' => 'and'
        ];

        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add a raw WHERE clause
     */
    public function whereRaw($sql, array $bindings = [])
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => 'and'
        ];

        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    /**
     * Add a WHERE EXISTS clause
     */
    public function whereExists($query)
    {
        $this->wheres[] = [
            'type' => 'exists',
            'query' => $query,
            'boolean' => 'and'
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT EXISTS clause
     */
    public function whereNotExists($query)
    {
        $this->wheres[] = [
            'type' => 'not_exists',
            'query' => $query,
            'boolean' => 'and'
        ];

        return $this;
    }

    /**
     * Add a date WHERE clause
     */
    public function whereDate($column, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->whereRaw("DATE({$column}) {$operator} ?", [$value]);
    }

    /**
     * Add a year WHERE clause
     */
    public function whereYear($column, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->whereRaw("YEAR({$column}) {$operator} ?", [$value]);
    }

    /**
     * Add a month WHERE clause
     */
    public function whereMonth($column, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->whereRaw("MONTH({$column}) {$operator} ?", [$value]);
    }

    /**
     * Add a day WHERE clause
     */
    public function whereDay($column, $operator, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->whereRaw("DAY({$column}) {$operator} ?", [$value]);
    }

    // Aggregation Methods

    /**
     * Get the maximum value
     */
    public function max($column)
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Get the minimum value
     */
    public function min($column)
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get the average value
     */
    public function avg($column)
    {
        return $this->aggregate('AVG', $column);
    }

    /**
     * Get the sum value
     */
    public function sum($column)
    {
        return $this->aggregate('SUM', $column);
    }

    /**
     * Execute an aggregate function
     */
    protected function aggregate($function, $column)
    {
        $originalSelects = $this->selects;
        $this->selects = ["{$function}({$column}) as aggregate"];
        
        $sql = $this->toSql();
        $connection = $this->model->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($this->bindings);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->selects = $originalSelects;
        
        return $result['aggregate'];
    }

    // Pagination

    /**
     * Paginate results
     */
    public function paginate($perPage = 15, $page = 1)
    {
        $total = $this->count();
        $offset = ($page - 1) * $perPage;
        
        $items = $this->limit($perPage)->offset($offset)->get();
        
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * Simple pagination
     */
    public function simplePaginate($perPage = 15, $page = 1)
    {
        $offset = ($page - 1) * $perPage;
        $items = $this->limit($perPage + 1)->offset($offset)->get();
        
        $hasMore = count($items) > $perPage;
        if ($hasMore) {
            array_pop($items);
        }
        
        return [
            'data' => $items,
            'per_page' => $perPage,
            'current_page' => $page,
            'has_more' => $hasMore
        ];
    }

    // Grouping and Having

    protected $groups = [];
    protected $havings = [];

    /**
     * Add a GROUP BY clause
     */
    public function groupBy(...$columns)
    {
        $this->groups = array_merge($this->groups, $columns);
        return $this;
    }

    /**
     * Add a HAVING clause
     */
    public function having($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'and'
        ];

        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Add an OR HAVING clause
     */
    public function orHaving($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'or'
        ];

        $this->bindings[] = $value;
        return $this;
    }

    // Soft Delete Support

    /**
     * Include soft deleted records
     */
    public function withTrashed()
    {
        // Remove any existing deleted_at constraints
        $this->wheres = array_filter($this->wheres, function($where) {
            return !($where['type'] === 'null' && $where['column'] === 'deleted_at');
        });
        
        return $this;
    }

    /**
     * Only get soft deleted records
     */
    public function onlyTrashed()
    {
        return $this->whereNotNull('deleted_at');
    }

    // Subqueries

    /**
     * Add a subquery WHERE clause
     */
    public function whereSubQuery($column, $operator, $callback)
    {
        $subQuery = new static($this->model);
        $callback($subQuery);
        
        $sql = $subQuery->toSql();
        $this->whereRaw("{$column} {$operator} ({$sql})", $subQuery->bindings);
        
        return $this;
    }

    // Distinct

    protected $distinct = false;

    /**
     * Add DISTINCT to the query
     */
    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    // Union

    protected $unions = [];

    /**
     * Add a UNION clause
     */
    public function union($query)
    {
        $this->unions[] = ['query' => $query, 'all' => false];
        return $this;
    }

    /**
     * Add a UNION ALL clause
     */
    public function unionAll($query)
    {
        $this->unions[] = ['query' => $query, 'all' => true];
        return $this;
    }

    // Enhanced toSql method - merged with original

    /**
     * Compile HAVING clauses
     */
    protected function compileHavings()
    {
        $clauses = [];

        foreach ($this->havings as $index => $having) {
            $boolean = $index === 0 ? '' : strtoupper($having['boolean']) . ' ';
            $clauses[] = $boolean . $having['column'] . ' ' . $having['operator'] . ' ?';
        }

        return implode(' ', $clauses);
    }

    // Chunk processing

    /**
     * Process results in chunks
     */
    public function chunk($size, $callback)
    {
        $page = 1;
        
        do {
            $results = $this->limit($size)->offset(($page - 1) * $size)->get();
            
            if (empty($results)) {
                break;
            }
            
            if ($callback($results) === false) {
                break;
            }
            
            $page++;
        } while (count($results) === $size);
    }

    // Debugging

    /**
     * Get the SQL query with bindings
     */
    public function toSqlWithBindings()
    {
        $sql = $this->toSql();
        
        foreach ($this->bindings as $binding) {
            if (is_string($binding)) {
                $value = "'{$binding}'";
            } elseif (is_null($binding)) {
                $value = 'NULL';
            } elseif (is_bool($binding)) {
                $value = $binding ? '1' : '0';
            } else {
                $value = (string)$binding;
            }
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }
        
        return $sql;
    }

    /**
     * Get the current bindings
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Dump the query
     */
    public function dd()
    {
        var_dump($this->toSqlWithBindings());
        die();
    }

    /**
     * Dump the query and continue
     */
    public function dump()
    {
        var_dump($this->toSqlWithBindings());
        return $this;
    }

    /**
     * Insert records
     */
    public function insert(array $data)
    {
        $connection = $this->model ? $this->model->getConnection() : $this->connection;
        $table = $this->model ? $this->model->getTable() : $this->table;
        
        if (empty($data)) {
            return false;
        }
        
        // Handle single row or multiple rows
        $rows = isset($data[0]) && is_array($data[0]) ? $data : [$data];
        
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $columnsList = implode(', ', $columns);
            
            $sql = "INSERT INTO {$table} ({$columnsList}) VALUES ({$placeholders})";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array_values($row));
        }
        
        return true;
    }

    /**
     * Update records
     */
    public function update(array $data)
    {
        $connection = $this->model ? $this->model->getConnection() : $this->connection;
        $table = $this->model ? $this->model->getTable() : $this->table;
        
        $set = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set);
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
            $values = array_merge($values, $this->bindings);
        }
        
        $stmt = $connection->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete records
     */
    public function delete()
    {
        $connection = $this->model ? $this->model->getConnection() : $this->connection;
        $table = $this->model ? $this->model->getTable() : $this->table;
        
        $sql = "DELETE FROM {$table}";
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->compileWheres();
        }
        
        $stmt = $connection->prepare($sql);
        return $stmt->execute($this->bindings);
    }
    
    /**
     * Handle dynamic method calls for scopes
     */
    public function __call($method, $parameters)
    {
        if ($this->model) {
            $scopeMethod = 'scope' . ucfirst($method);
            if (method_exists($this->model, $scopeMethod)) {
                return $this->model->$scopeMethod($this, ...$parameters);
            }
        }
        
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
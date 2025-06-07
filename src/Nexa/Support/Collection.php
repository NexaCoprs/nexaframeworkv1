<?php

namespace Nexa\Support;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;

class Collection implements ArrayAccess, Countable, Iterator, JsonSerializable
{
    protected $items = [];
    protected $position = 0;

    public function __construct($items = [])
    {
        $this->items = is_array($items) ? $items : [$items];
    }

    // Fluent methods for modern development
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    public function filter(callable $callback = null)
    {
        if ($callback === null) {
            return new static(array_filter($this->items));
        }
        return new static(array_filter($this->items, $callback));
    }

    public function where($key, $operator = null, $value = null)
    {
        if ($operator === null) {
            $value = $operator;
            $operator = '=';
        }

        return $this->filter(function ($item) use ($key, $operator, $value) {
            $itemValue = is_array($item) ? $item[$key] : $item->$key;
            
            switch ($operator) {
                case '=':
                case '==':
                    return $itemValue == $value;
                case '!=':
                case '<>':
                    return $itemValue != $value;
                case '>':
                    return $itemValue > $value;
                case '<':
                    return $itemValue < $value;
                case '>=':
                    return $itemValue >= $value;
                case '<=':
                    return $itemValue <= $value;
                default:
                    return $itemValue == $value;
            }
        });
    }

    public function pluck($key)
    {
        return $this->map(function ($item) use ($key) {
            return is_array($item) ? $item[$key] : $item->$key;
        });
    }

    public function first(callable $callback = null)
    {
        if ($callback === null) {
            return reset($this->items);
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function last(callable $callback = null)
    {
        if ($callback === null) {
            return end($this->items);
        }

        $items = array_reverse($this->items, true);
        foreach ($items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function take($limit)
    {
        return new static(array_slice($this->items, 0, $limit));
    }

    public function skip($offset)
    {
        return new static(array_slice($this->items, $offset));
    }

    public function chunk($size)
    {
        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }
        return new static($chunks);
    }

    public function sort(callable $callback = null)
    {
        $items = $this->items;
        
        if ($callback) {
            uasort($items, $callback);
        } else {
            asort($items);
        }
        
        return new static($items);
    }

    public function sortBy($key)
    {
        return $this->sort(function ($a, $b) use ($key) {
            $aValue = is_array($a) ? $a[$key] : $a->$key;
            $bValue = is_array($b) ? $b[$key] : $b->$key;
            return $aValue <=> $bValue;
        });
    }

    public function groupBy($key)
    {
        $groups = [];
        
        foreach ($this->items as $item) {
            $groupKey = is_array($item) ? $item[$key] : $item->$key;
            $groups[$groupKey][] = $item;
        }
        
        return new static(array_map(function ($group) {
            return new static($group);
        }, $groups));
    }

    public function unique($key = null)
    {
        if ($key === null) {
            return new static(array_unique($this->items));
        }

        $unique = [];
        $seen = [];
        
        foreach ($this->items as $item) {
            $value = is_array($item) ? $item[$key] : $item->$key;
            if (!in_array($value, $seen)) {
                $seen[] = $value;
                $unique[] = $item;
            }
        }
        
        return new static($unique);
    }

    public function sum($key = null)
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        return $this->pluck($key)->sum();
    }

    public function avg($key = null)
    {
        $count = $this->count();
        return $count > 0 ? $this->sum($key) / $count : 0;
    }

    public function min($key = null)
    {
        if ($key === null) {
            return min($this->items);
        }

        return $this->pluck($key)->min();
    }

    public function max($key = null)
    {
        if ($key === null) {
            return max($this->items);
        }

        return $this->pluck($key)->max();
    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    public function contains($key, $value = null)
    {
        if ($value === null) {
            return in_array($key, $this->items);
        }

        return $this->where($key, $value)->isNotEmpty();
    }

    public function toArray()
    {
        return $this->items;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->items);
    }

    // Iterator implementation
    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return array_values($this->items)[$this->position];
    }

    public function key(): mixed
    {
        return array_keys($this->items)[$this->position];
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset(array_values($this->items)[$this->position]);
    }

    // JsonSerializable implementation
    public function jsonSerialize(): mixed
    {
        return $this->items;
    }

    // Magic methods
    public function __toString()
    {
        return $this->toJson();
    }
}
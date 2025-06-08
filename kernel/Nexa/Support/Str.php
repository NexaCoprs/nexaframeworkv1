<?php

namespace Nexa\Support;

class Str
{
    protected $value;

    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    // Static factory method
    public static function of($value)
    {
        return new static($value);
    }

    // Fluent string manipulation methods
    public function upper()
    {
        return new static(strtoupper($this->value));
    }

    public function lower()
    {
        return new static(strtolower($this->value));
    }

    public function title()
    {
        return new static(ucwords(strtolower($this->value)));
    }

    public function camel()
    {
        return new static(lcfirst($this->studly()->value));
    }

    public function studly()
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $this->value));
        return new static(str_replace(' ', '', $value));
    }

    public function snake($delimiter = '_')
    {
        $value = preg_replace('/\s+/u', '', ucwords($this->value));
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value);
        return new static(strtolower($value));
    }

    public function kebab()
    {
        return $this->snake('-');
    }

    public function slug($separator = '-')
    {
        $value = preg_replace('/[^\w\s-]/u', '', $this->value);
        $value = preg_replace('/[-\s]+/', $separator, $value);
        return new static(trim(strtolower($value), $separator));
    }

    public function trim($characters = null)
    {
        return new static($characters ? trim($this->value, $characters) : trim($this->value));
    }

    public function ltrim($characters = null)
    {
        return new static($characters ? ltrim($this->value, $characters) : ltrim($this->value));
    }

    public function rtrim($characters = null)
    {
        return new static($characters ? rtrim($this->value, $characters) : rtrim($this->value));
    }

    public function replace($search, $replace)
    {
        return new static(str_replace($search, $replace, $this->value));
    }

    public function replaceFirst($search, $replace)
    {
        $pos = strpos($this->value, $search);
        if ($pos !== false) {
            return new static(substr_replace($this->value, $replace, $pos, strlen($search)));
        }
        return new static($this->value);
    }

    public function replaceLast($search, $replace)
    {
        $pos = strrpos($this->value, $search);
        if ($pos !== false) {
            return new static(substr_replace($this->value, $replace, $pos, strlen($search)));
        }
        return new static($this->value);
    }

    public function substr($start, $length = null)
    {
        return new static(substr($this->value, $start, $length));
    }

    public function limit($limit = 100, $end = '...')
    {
        if (strlen($this->value) <= $limit) {
            return new static($this->value);
        }
        return new static(substr($this->value, 0, $limit) . $end);
    }

    public function words($words = 100, $end = '...')
    {
        $wordArray = explode(' ', $this->value);
        if (count($wordArray) <= $words) {
            return new static($this->value);
        }
        return new static(implode(' ', array_slice($wordArray, 0, $words)) . $end);
    }

    public function append($value)
    {
        return new static($this->value . $value);
    }

    public function prepend($value)
    {
        return new static($value . $this->value);
    }

    public function start($prefix)
    {
        if (!$this->startsWith($prefix)) {
            return new static($prefix . $this->value);
        }
        return new static($this->value);
    }

    public function finish($suffix)
    {
        if (!$this->endsWith($suffix)) {
            return new static($this->value . $suffix);
        }
        return new static($this->value);
    }

    public function startsWith($needle)
    {
        return strpos($this->value, $needle) === 0;
    }

    public function endsWith($needle)
    {
        return substr($this->value, -strlen($needle)) === $needle;
    }

    public function contains($needle)
    {
        return strpos($this->value, $needle) !== false;
    }

    public function containsAll(array $needles)
    {
        foreach ($needles as $needle) {
            if (!$this->contains($needle)) {
                return false;
            }
        }
        return true;
    }

    public function split($delimiter)
    {
        return collect(explode($delimiter, $this->value));
    }

    public function explode($delimiter)
    {
        return $this->split($delimiter);
    }

    public function length()
    {
        return strlen($this->value);
    }

    public function isEmpty()
    {
        return $this->value === '';
    }

    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    public function isJson()
    {
        json_decode($this->value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function isEmail()
    {
        return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isUrl()
    {
        return filter_var($this->value, FILTER_VALIDATE_URL) !== false;
    }

    public function isNumeric()
    {
        return is_numeric($this->value);
    }

    public function toArray()
    {
        return str_split($this->value);
    }

    public function toString()
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }

    // Static helper methods
    public static function random($length = 16)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return new static($randomString);
    }

    public static function uuid()
    {
        return new static(sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        ));
    }

    public static function plural($value, $count = 2)
    {
        if ($count === 1) {
            return $value;
        }
        
        // Simple pluralization rules
        if (substr($value, -1) === 'y') {
            return substr($value, 0, -1) . 'ies';
        }
        
        if (in_array(substr($value, -1), ['s', 'x', 'z']) || in_array(substr($value, -2), ['ch', 'sh'])) {
            return $value . 'es';
        }
        
        return $value . 's';
    }

    public static function singular($value)
    {
        // Simple singularization rules
        if (substr($value, -3) === 'ies') {
            return substr($value, 0, -3) . 'y';
        }
        
        if (substr($value, -2) === 'es') {
            return substr($value, 0, -2);
        }
        
        if (substr($value, -1) === 's') {
            return substr($value, 0, -1);
        }
        
        return $value;
    }
}
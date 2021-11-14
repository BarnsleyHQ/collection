<?php

namespace AlexBarnsley;

use Adbar\Dot as DotNotation;

class Collection {
    private $entries;
    private $cursor = -1;

    public function __construct($data = [])
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }

        $this->entries = (array) $data;
    }

    public function count()
    {
        return count($this->entries);
    }

    public function add($value)
    {
        $this->entries[] = $value;

        return $this;
    }

    public function get($key)
    {
        if ($this->has($key)) {
            return $this->entries[$key];
        }

        return null;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->entries);
    }

    public function first()
    {
        return $this->get(0);
    }

    public function last()
    {
        return $this->get(count($this->entries) - 1);
    }

    public function next()
    {
        if (! array_key_exists($this->cursor + 1, $this->entries)) {
            return null;
        }

        return $this->entries[++$this->cursor];
    }

    public function previous()
    {
        if ($this->cursor <= 0) {
            $this->cursor = -1;

            return null;
        }

        return $this->entries[--$this->cursor];
    }

    public function keyBy(string $key)
    {
        $array = [];

        foreach ($this->entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            if (array_key_exists($key, $entry)) {
                $array[$entry[$key]] = $entry;
            }
        }

        return collect($array);
    }

    public function find(Callable $callback)
    {
        return $this->whereCallable($callback)->first();
    }

    public function map(Callable $callback)
    {
        return collect(array_map($callback, $this->entries));
    }

    public function where(string $key, $value)
    {
        $results = collect();
        foreach ($this->entries as $entry) {
            $entryArray = (array) $entry;
            $notation = new DotNotation($entryArray);

            if (preg_match('/(is|has)/', $key) && method_exists($entry, $key)) {
                if ($entry->{$key}() === $value) {
                    $results->add($entry);
                }

                continue;
            }

            if (! $notation->has($key) || $notation->get($key) !== $value) {
                continue;
            }

            $results->add($entry);
        }

        return $results;
    }

    public function whereCallable(Callable $callback)
    {
        $results = collect();
        foreach ($this->entries as $entry) {
            if (! $callback($entry)) {
                continue;
            }

            $results->add($entry);
        }

        return $results;
    }

    public function &items()
    {
        return $this->entries;
    }

    public function toArray()
    {
        return $this->entries;
    }

    public function isEmpty()
    {
        return count($this->entries) === 0;
    }
}

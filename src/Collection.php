<?php

namespace AlexBarnsley;

use Adbar\Dot as DotNotation;

class Collection {
    private $entries;
    private $cursor = -1;

    public function __construct(array $entries = [])
    {
        $this->entries = $entries;
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

    public function get(int $index)
    {
        if (!empty($this->entries)) {
            return $this->entries[$index];
        }

        return null;
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

    public function where(string $key, $value)
    {
        $results = collect();
        foreach ($this->entries as $entry) {
            $entryArray = (array) $entry;
            $notation = new DotNotation($entryArray);

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
}

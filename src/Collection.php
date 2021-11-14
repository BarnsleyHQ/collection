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

    public function count(): int
    {
        return count($this->entries);
    }

    public function add($value): self
    {
        $this->entries[] = $value;

        return $this;
    }

    public function set($key, $value): self
    {
        $this->entries[$key] = $value;

        return $this;
    }

    public function &get($key)
    {
        $result = null;
        if ($this->has($key)) {
            $result = &$this->entries[$key];
        }

        return $result;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->entries);
    }

    public function &first()
    {
        return $this->get(0);
    }

    public function &last()
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

    public function keyBy(string $key): self
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

    public function map(Callable $callback): self
    {
        return collect(array_map($callback, $this->entries));
    }

    public function where(string $key, $value): self
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

    public function whereCallable(Callable $callback): self
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

    public function sortBy(string $key, string $direction): self
    {
        if (in_array($direction, ['asc', 'desc'], true) === false) {
            throw new \Exception('sortBy#direction must be "asc" or "desc"');
        }

        usort($this->entries, function ($a, $b) use ($key, $direction) {
            $aNotation = new DotNotation($a);
            $bNotation = new DotNotation($b);

            if (! $aNotation->has($key) && ! $bNotation->has($key)) {
                return 0;
            } else if (! $aNotation->has($key) || ! $bNotation->has($key)) {
                return $aNotation->has($key) ? -1 : 1;
            }

            $aValue = $aNotation->get($key);
            $bValue = $bNotation->get($key);
            $sortMethod = 'strnatcasecmp';
            if (is_int($aValue) && is_int($bValue)) {
                $sortMethod = 'strcmp';
            }

            if ($direction === 'asc') {
                return $sortMethod($aValue, $bValue);
            }

            return $sortMethod($bValue, $aValue);
        });

        return $this;
    }

    public function &items(): array
    {
        return $this->entries;
    }

    public function toArray(): array
    {
        return $this->entries;
    }

    public function isEmpty(): bool
    {
        return count($this->entries) === 0;
    }
}

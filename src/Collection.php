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

    public function push($value): self
    {
        return $this->add($value);
    }

    public function concat(self $collection): self
    {
        $isAssociative = $collection->isAssoc();
        foreach ($collection->toArray() as $key => $value) {
            if ($isAssociative) {
                $this->set($key, $value);

                continue;
            }

            $this->add($value);
        }

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
            $notation = new DotNotation((array) $entry);

            if (preg_match('/^(is|has)/', $key) && method_exists($entry, $key)) {
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

    public function sort(Callable $callback): self
    {
        usort($this->entries, $callback);

        return $this;
    }

    public function sortBy(string $key, string $direction): self
    {
        if (in_array($direction, ['asc', 'desc'], true) === false) {
            throw new \Exception('sortBy#direction must be "asc" or "desc"');
        }

        usort($this->entries, function ($a, $b) use ($key, $direction) {
            $aValue = null;
            $bValue = null;
            $methodValuesSet = false;

            $aHasMethod = is_array($a) === false && method_exists($a, $key);
            $bHasMethod = is_array($b) === false && method_exists($b, $key);
            if ($aHasMethod || $bHasMethod) {
                $methodValuesSet = true;

                if ($aHasMethod) {
                    $aValue = $a->{$key}();
                }

                if ($bHasMethod) {
                    $bValue = $b->{$key}();
                }
            }

            if (! $methodValuesSet) {
                $aNotation = new DotNotation((array) $a);
                $bNotation = new DotNotation((array) $b);

                if (! $aNotation->has($key) && ! $bNotation->has($key)) {
                    return 0;
                } else if (! $aNotation->has($key) || ! $bNotation->has($key)) {
                    return $aNotation->has($key) ? -1 : 1;
                }

                $aValue = $aNotation->get($key);
                $bValue = $bNotation->get($key);
            }

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

    public function groupBy(string $key): self
    {
        $results = collect();
        foreach ($this->entries as $entry) {
            $notation = new DotNotation((array) $entry);

            $value = null;
            if (preg_match('/^(get)/', $key) && method_exists($entry, $key)) {
                $value = $entry->{$key}();
            }

            if ($value === null && (! $notation->has($key) || $notation->get($key) === null)) {
                continue;
            }

            $value = $value ?: $notation->get($key);
            if ($value === null || $value === '') {
                continue;
            }

            if (! $results->has($value)) {
                $results->set($value, collect());
            }

            $results->get($value)->add($entry);
        }

        return $results;
    }

    public function isEmpty(): bool
    {
        return count($this->entries) === 0;
    }

    public function isAssoc(): bool
    {
        if (count($this->entries) === 0) {
            return false;
        }

        return array_keys($this->entries) !== array_keys(array_values($this->entries));
    }

    public function &items(): array
    {
        return $this->entries;
    }

    public function keys(): array
    {
        return array_keys($this->entries);
    }

    public function values(): array
    {
        return array_values($this->entries);
    }

    public function toArray(bool $includeNested = false): array
    {
        if (! $includeNested) {
            return $this->entries;
        }

        $array = [];

        foreach ($this->entries as $key => $value) {
            if (is_object($value) && get_class($value) === self::class) {
                $value = $value->toArray();
            }

            $array[$key] = $value;
        }

        return $array;
    }
}

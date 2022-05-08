<?php

namespace AlexBarnsley;

use ArrayAccess;
use ArrayIterator;
use Adbar\Dot as DotNotation;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected $entries;

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
        if ($this->count() === 0) {
            $value = null;

            return $value;
        }

        return $this->get($this->keys()[0]);
    }

    public function &last()
    {
        return $this->get(count($this->entries) - 1);
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

            if (preg_match('/^(is|has|get)/', $key) && method_exists($entry, $key)) {
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

    public function filter(Callable $callback): self
    {
        return $this->whereCallable($callback);
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

    public function pluck($key): array
    {
        $data = [];
        foreach ($this->entries as $entry) {
            if (! isset($entry[$key])) {
                continue;
            }

            $data[] = $entry[$key];
        }

        return $data;
    }

    public function join(string $separator): string
    {
        return implode($separator, $this->entries);
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

    public function unique(int $flags = SORT_STRING): array
    {
        return collect(array_unique($this->entries))->values();
    }

    public function uniqueWithKeys(int $flags = SORT_STRING): array
    {
        return array_unique($this->entries);
    }

    public function toArray(): array
    {
        $includeNested = true;
        if (count(func_get_args()) >= 1 && gettype(func_get_arg(0)) === 'boolean') {
            $includeNested = func_get_arg(0);
        }

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

    public function getIterator()
    {
        return new ArrayIterator($this->entries);
    }

    public function offsetExists($key)
    {
        return isset($this->entries[$key]);
    }

    public function offsetGet($key)
    {
        return isset($this->entries[$key]) ? $this->entries[$key] : null;
    }

    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->entries[] = $value;
        } else {
            $this->entries[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        unset($this->entries[$key]);
    }
}

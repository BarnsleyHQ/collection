<?php

namespace BarnsleyHQ;

use ArrayAccess;
use ArrayIterator;
use Adbar\Dot as DotNotation;
use Countable;
use IteratorAggregate;

class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    protected $entries;
    protected $cursor = -1;

    public static function __set_state(array $data): self
    {
        return new self($data['entries']);
    }

    public static function make($data = []): self
    {
        return new self($data);
    }

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

    public function sum(): int
    {
        return array_sum($this->entries);
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

    public function remove($key): self
    {
        $collection = new self();
        foreach ($this->entries as $entryKey => $entry) {
            if ($entryKey === $key) {
                continue;
            }

            $collection->set($entryKey, $entry);
        }

        return $collection;
    }

    public function take(int $count): self
    {
        return new self(array_slice(
            $this->entries,
            -$count
        ));
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->entries));
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

    public function read($key)
    {
        $notation = new DotNotation($this->entries);
        if ($notation->has($key)) {
            return $notation->get($key);
        }

        return null;
    }

    public function chunk(int $count): self
    {
        return new self(array_chunk($this->entries, $count));
    }

    public function replace(string $find, string $replace, $key = null): self
    {
        $method = 'str_replace';
        if ($find[0] === '/') {
            $method = 'preg_replace';
        }

        foreach ($this->entries as &$entry) {
            if ($key) {
                $entry[$key] = $method($find, $replace, $entry[$key]);

                continue;
            }

            $entry = $method($find, $replace, $entry);
        }

        return $this;
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
            if (! is_array($entry) && ! is_object($entry)) {
                continue;
            }

            if (is_object($entry) && property_exists($entry, $key)) {
                $array[$entry->$key] = $entry;
            } else if (array_key_exists($key, $entry)) {
                $array[$entry[$key]] = $entry;
            }
        }

        return new self($array);
    }

    public function find(Callable $callback)
    {
        return $this->whereCallable($callback)->first();
    }

    public function map(Callable $callback): self
    {
        return new self(array_map($callback, $this->entries));
    }

    public function where(string $key, $value): self
    {
        $results = new self();
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
        $results = new self();
        foreach ($this->entries as $key => $entry) {
            if (! $callback($entry)) {
                continue;
            }

            $results->set($key, $entry);
        }

        return $results;
    }

    public function filter(Callable $callback): self
    {
        return $this->whereCallable($callback);
    }

    public function flip(): self
    {
        return new self(array_flip($this->entries));
    }

    public function sort(Callable $callback): self
    {
        usort($this->entries, $callback);

        return $this;
    }

    public function sortWithKeys(Callable $callback): self
    {
        uasort($this->entries, $callback);

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
        $results = new self();
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
                $results->set($value, new self());
            }

            $results->get($value)->add($entry);
        }

        return $results;
    }

    public function dd()
    {
        dd($this);
    }

    public function pluck($key, $keyBy = null): Collection
    {
        $data = [];
        foreach ($this->entries as $originalKey => $entry) {
            $value = null;
            $haveGotValue = false;
            if (preg_match('/^(is|has|get)/', $key) && method_exists($entry, $key)) {
                $value = $entry->{$key}();

                $haveGotValue = true;
            }

            if (! $haveGotValue) {
                $notation = new DotNotation($entry);
                if (! $notation->has($key)) {
                    continue;
                }

                $value = $notation->get($key);
            }

            if ($keyBy) {
                $haveGotValue = false;
                if (preg_match('/^(is|has|get)/', $keyBy) && method_exists($entry, $keyBy)) {
                    $entryKey = $entry->{$keyBy}();

                    $haveGotValue = true;
                }

                if (! $haveGotValue) {
                    $entryKey = $notation->get($keyBy, '');
                }

                $data[$entryKey ?? ''] = $value;
            } else {
                $data[$originalKey] = $value;
            }
        }

        return new self($data);
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

    public function valuesAsCollection(): self
    {
        return new self(array_values($this->entries));
    }

    public function unique(int $flags = SORT_STRING): self
    {
        return (new self(array_unique($this->entries)))->valuesAsCollection();
    }

    public function uniqueWithKeys(int $flags = SORT_STRING): self
    {
        return new self(array_unique($this->entries));
    }

    public function toArray(bool $includeNested = true): array
    {
        if (! $includeNested) {
            return $this->entries;
        }

        $array = [];
        foreach ($this->entries as $key => $value) {
            if (is_object($value) && get_class($value) === self::class || method_exists($value, 'toArray')) {
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

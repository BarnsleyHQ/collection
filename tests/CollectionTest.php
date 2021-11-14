<?php

use AlexBarnsley\Collection;
use PHPUnit\Framework\TestCase;

class SampleEntity {
    public bool $boolean = false;

    public function isTrue(): bool
    {
        return $this->boolean === true;
    }
}

class CollectionTest extends TestCase
{
    private $collection;

    protected function setUp(): void
    {
        $this->collection = new Collection([
            'test1',
            'test2',
            'test3',
            'test4',
            'test5',
        ]);
    }

    public function testHandlesNewInstance()
    {
        $collection = new Collection([1, 2, 3]);

        $this->assertEquals([1, 2, 3], (new Collection($collection))->toArray());
    }

    public function testHandlesNewNonArrayInstance()
    {
        $collection = new Collection('test');

        $this->assertEquals(['test'], $collection->toArray());
    }

    public function testCount()
    {
        $this->assertEquals(5, $this->collection->count());
    }

    public function testAdd()
    {
        $this->assertEquals([
            'test1',
            'test2',
            'test3',
            'test4',
            'test5',
        ], $this->collection->toArray());

        $this->collection->add('new test');

        $this->assertEquals([
            'test1',
            'test2',
            'test3',
            'test4',
            'test5',
            'new test',
        ], $this->collection->toArray());
    }

    public function testFirst()
    {
        $this->assertEquals('test1', $this->collection->first());
    }

    public function testFirstShouldReturnNull()
    {
        $this->assertEquals(null, (new Collection([]))->first());
    }

    public function testLast()
    {
        $this->assertEquals('test5', $this->collection->last());
    }

    public function testLastShouldReturnNull()
    {
        $this->assertEquals(null, (new Collection([]))->last());
    }

    public function testNext()
    {
        $this->assertEquals('test1', $this->collection->next());
        $this->assertEquals('test2', $this->collection->next());
        $this->assertEquals('test3', $this->collection->next());
        $this->assertEquals('test4', $this->collection->next());
        $this->assertEquals('test5', $this->collection->next());
        $this->assertEquals(null, $this->collection->next());
    }

    public function testPrevious()
    {
        $this->assertEquals(null, $this->collection->previous());
        $this->collection->next();
        $this->collection->next();
        $this->collection->next();
        $this->collection->next();
        $this->assertEquals('test3', $this->collection->previous());
        $this->assertEquals('test2', $this->collection->previous());
        $this->assertEquals('test1', $this->collection->previous());
        $this->assertEquals(null, $this->collection->previous());
    }

    public function testKeyBy()
    {
        $collection = new Collection([
            ['name' => 'alex', 'age' => '30'],
            ['name' => 'zoe', 'age' => '31'],
            ['name' => 'bob', 'age' => '32'],
            'test',
        ]);

        $keyed = $collection->keyBy('name');

        $this->assertEquals([
            'alex' => ['name' => 'alex', 'age' => '30'],
            'zoe'  => ['name' => 'zoe', 'age' => '31'],
            'bob'  => ['name' => 'bob', 'age' => '32'],
        ], $keyed->toArray());
    }

    public function testFind()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'fran', 'age' => 19],
        ]);

        $result = $this->collection
            ->find(fn ($item) => $item['age'] === 30);

        $this->assertEquals(['name' => 'alex', 'age' => 30], $result);
    }

    public function testMap()
    {
        $this->collection = new Collection([1, 2, 3, 4]);
        $mapped = $this->collection->map(function($entry) {
            return $entry + 1;
        });

        $this->assertEquals([2, 3, 4, 5], $mapped->toArray());
    }

    public function testWhere()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'fran', 'age' => 19],
        ]);

        $whereItems = $this->collection->where('age', 30);

        $this->assertEquals([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'billie', 'age' => 30],
        ], $whereItems->toArray());
    }

    public function testWhereDotNotation()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30, 'address' => ['postcode' => 'AB1 23C']],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bob', 'age' => 28, 'address' => ['postcode' => 'AB2 34C']],
            ['name' => 'billie', 'age' => 30, 'address' => ['postcode' => 'AB1 23D']],
            ['name' => 'fran', 'age' => 19, 'address' => ['postcode' => 'AB1 23C']],
        ]);

        $whereItems = $this->collection->where('address.postcode', 'AB1 23C');

        $this->assertEquals([
            ['name' => 'alex', 'age' => 30, 'address' => ['postcode' => 'AB1 23C']],
            ['name' => 'fran', 'age' => 19, 'address' => ['postcode' => 'AB1 23C']],
        ], $whereItems->toArray());
    }

    public function testWhereObjectMethod()
    {
        $entityOne = new SampleEntity();
        $entityTwo = new SampleEntity();
        $entityTwo->boolean = true;

        $this->collection = new Collection([
            $entityOne,
            $entityTwo,
        ]);

        $whereItems = $this->collection->where('isTrue', true);

        $this->assertEquals([
            $entityTwo,
        ], $whereItems->toArray());

        $whereItems = $this->collection->where('isTrue', false);

        $this->assertEquals([
            $entityOne,
        ], $whereItems->toArray());
    }

    public function testWhereShouldSkipIfKeyDoesntExist()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'zoe'],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'fran', 'age' => 19],
        ]);

        $whereItems = $this->collection->where('age', 33);

        $this->assertEquals([], $whereItems->toArray());

        $whereItems = $this->collection->where('test', true);

        $this->assertEquals([], $whereItems->toArray());
    }

    public function testWhereCallable()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'fran', 'age' => 19],
        ]);

        $whereItems = $this->collection
            ->whereCallable(fn ($item) => $item['age'] === 30);

        $this->assertEquals([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'billie', 'age' => 30],
        ], $whereItems->toArray());
    }

    public function testItemsPointer()
    {
        $this->assertEquals('test1', $this->collection->first());

        $this->collection->items()[0] = 'pointer1';

        $this->assertEquals('pointer1', $this->collection->first());
    }

    public function testToArray()
    {
        $this->assertTrue(is_array($this->collection->toArray()));
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->collection->isEmpty());
        $this->assertTrue((new Collection())->isEmpty());
    }
}

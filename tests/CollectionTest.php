<?php

use AlexBarnsley\Collection;
use PHPUnit\Framework\TestCase;

class SampleEntity {
    public bool $boolean = false;
    public string $name = '';

    public function isTrue(): bool
    {
        return $this->boolean === true;
    }

    public function getisTrue(): bool
    {
        return false;
    }

    public function hasTrue(): bool
    {
        return $this->boolean === true;
    }

    public function getBoolean(): bool
    {
        return $this->boolean === true;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function testPush()
    {
        $this->assertEquals([
            'test1',
            'test2',
            'test3',
            'test4',
            'test5',
        ], $this->collection->toArray());

        $this->collection->push('new test');

        $this->assertEquals([
            'test1',
            'test2',
            'test3',
            'test4',
            'test5',
            'new test',
        ], $this->collection->toArray());
    }

    public function testSet()
    {
        $this->assertEquals('test3', $this->collection->get(2));

        $this->collection->set(2, 'updated test');
        $this->collection->set(10, 'new test');

        $this->assertEquals('updated test', $this->collection->get(2));
        $this->assertEquals('new test', $this->collection->get(10));
    }

    public function testGet()
    {
        $this->assertEquals('test3', $this->collection->get(2));
        $this->assertNull($this->collection->get(200));
    }

    public function testGetAssociative()
    {
        $collection = new Collection(['name' => 'alex', 'age' => '30']);

        $this->assertEquals('alex', $collection->get('name'));
        $this->assertEquals(null, $collection->get('location'));
    }

    public function testGetAsPointer()
    {
        $collection = new Collection([
            ['name' => 'alex', 'age' => '30']
        ]);

        $reference = $collection->get(0)['name'] = 'bob';

        $this->assertEquals('bob', $collection->get(0)['name']);
    }

    public function testHas()
    {
        $this->assertTrue($this->collection->has(2));
        $this->assertFalse($this->collection->has(200));
    }

    public function testHasAssociative()
    {
        $collection = new Collection(['name' => 'alex', 'age' => '30']);

        $this->assertTrue($collection->has('name'));
        $this->assertFalse($collection->has('location'));
    }

    public function testFirst()
    {
        $this->assertEquals('test1', $this->collection->first());
    }

    public function testFirstShouldReturnNull()
    {
        $this->assertEquals(null, (new Collection([]))->first());
    }

    public function testFirstAsPointer()
    {
        $collection = new Collection([
            ['name' => 'alex', 'age' => '30']
        ]);

        $reference = $collection->first()['name'] = 'bob';

        $this->assertEquals('bob', $collection->get(0)['name']);
    }

    public function testLast()
    {
        $this->assertEquals('test5', $this->collection->last());
    }

    public function testLastShouldReturnNull()
    {
        $this->assertEquals(null, (new Collection([]))->last());
    }

    public function testLastAsPointer()
    {
        $collection = new Collection([
            ['name' => 'alex', 'age' => '30']
        ]);

        $reference = $collection->last()['name'] = 'bob';

        $this->assertEquals('bob', $collection->get(0)['name']);
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

        $whereItems = $this->collection->where('hasTrue', false);

        $this->assertEquals([
            $entityOne,
        ], $whereItems->toArray());

        $whereItems = $this->collection->where('getBoolean', false);

        $this->assertEquals([], $whereItems->toArray());
    }

    public function testWhereObjectInvalidMethod()
    {
        $entityOne = new SampleEntity();
        $entityTwo = new SampleEntity();
        $entityTwo->boolean = true;

        $this->collection = new Collection([
            $entityOne,
            $entityTwo,
        ]);

        $whereItems = $this->collection->where('getisTrue', true);

        $this->assertEquals([], $whereItems->toArray());

        $whereItems = $this->collection->where('getisTrue', false);

        $this->assertEquals([], $whereItems->toArray());
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

    public function testSort()
    {
        $this->collection = new Collection([
            'alex',
            'zoe',
            'bob',
            'billie',
            'fran',
        ]);

        $this->collection->sort('strcmp');

        $this->assertEquals([
            'alex',
            'billie',
            'bob',
            'fran',
            'zoe',
        ], $this->collection->toArray());
    }

    public function testSortBy()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'fran', 'age' => 19],
        ]);

        $this->assertEquals([
            ['name' => 'fran', 'age' => 19],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'alex', 'age' => 30],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
        ], $this->collection->sortBy('age', 'asc')->toArray());
        $this->assertEquals([
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'alex', 'age' => 30],
            ['name' => 'billie', 'age' => 30],
            ['name' => 'bob', 'age' => 28],
            ['name' => 'fran', 'age' => 19],
        ], $this->collection->sortBy('age', 'desc')->toArray());
    }

    public function testSortByDotNotation()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30, 'children' => ['count' => 6]],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bob', 'age' => 28, 'children' => ['count' => 3]],
            ['name' => 'billie', 'age' => 30, 'children' => ['count' => 1]],
            ['name' => 'fran', 'age' => 19, 'children' => ['count' => 1]],
            ['name' => 'charlie', 'age' => 19],
        ]);

        $this->assertEquals([
            ['name' => 'billie', 'age' => 30, 'children' => ['count' => 1]],
            ['name' => 'fran', 'age' => 19, 'children' => ['count' => 1]],
            ['name' => 'bob', 'age' => 28, 'children' => ['count' => 3]],
            ['name' => 'alex', 'age' => 30, 'children' => ['count' => 6]],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'charlie', 'age' => 19],
        ], $this->collection->sortBy('children.count', 'asc')->toArray());
        $this->assertEquals([
            ['name' => 'alex', 'age' => 30, 'children' => ['count' => 6]],
            ['name' => 'bob', 'age' => 28, 'children' => ['count' => 3]],
            ['name' => 'billie', 'age' => 30, 'children' => ['count' => 1]],
            ['name' => 'fran', 'age' => 19, 'children' => ['count' => 1]],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'charlie', 'age' => 19],
        ], $this->collection->sortBy('children.count', 'desc')->toArray());
    }

    public function testSortByObjectMethod()
    {
        $entityOne = new SampleEntity();
        $entityTwo = new SampleEntity();
        $entityThree = new SampleEntity();
        $entityOne->name = 'bob';
        $entityTwo->name = 'alex';

        $this->collection = new Collection([
            $entityOne,
            $entityTwo,
            $entityThree,
        ]);

        $this->assertEquals([
            $entityThree,
            $entityTwo,
            $entityOne,
        ], $this->collection->sortBy('getName', 'asc')->toArray());
        $this->assertEquals([
            $entityOne,
            $entityTwo,
            $entityThree,
        ], $this->collection->sortBy('getName', 'desc')->toArray());
    }

    public function testSortByInvalidDirection()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
        ]);

        $this->expectExceptionMessage('sortBy#direction must be "asc" or "desc"');
        $this->collection->sortBy('age', 'up');
    }

    public function testGroupBy()
    {
        $this->collection = new Collection([
            ['name' => 'alex', 'age' => 30],
            ['name' => 'charlie', 'age' => 30],
            ['name' => 'zoe', 'age' => 33],
            ['name' => 'bill'],
        ]);

        $this->assertEquals([
            30 => [
                ['name' => 'alex', 'age' => 30],
                ['name' => 'charlie', 'age' => 30],
            ],
            33 => [
                ['name' => 'zoe', 'age' => 33],
            ],
        ], $this->collection->groupBy('age')->toArray(true));
    }

    public function testGroupByMethod()
    {
        $entityOne = new SampleEntity();
        $entityTwo = new SampleEntity();
        $entityThree = new SampleEntity();
        $entityFour = new SampleEntity();
        $entityOne->name = 'bob';
        $entityTwo->name = 'alex';
        $entityFour->name = 'bob';

        $this->collection = new Collection([
            $entityOne,
            $entityTwo,
            $entityThree,
            $entityFour,
        ]);

        $this->assertEquals([
            'bob' => [
                $entityOne,
                $entityFour,
            ],
            'alex' => [
                $entityTwo,
            ],
        ], $this->collection->groupBy('getName')->toArray(true));
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->collection->isEmpty());
        $this->assertTrue((new Collection())->isEmpty());
    }

    public function testIsAssoc()
    {
        $assocCollection = new Collection([
            30 => [
                ['name' => 'alex', 'age' => 30],
                ['name' => 'charlie', 'age' => 30],
            ],
            33 => [
                ['name' => 'zoe', 'age' => 33],
            ],
        ]);

        $this->assertTrue($assocCollection->isAssoc());
        $this->assertFalse($this->collection->isAssoc());
        $this->assertFalse((new Collection())->isAssoc());
    }

    public function testItemsPointer()
    {
        $this->assertEquals('test1', $this->collection->first());

        $this->collection->items()[0] = 'pointer1';

        $this->assertEquals('pointer1', $this->collection->first());
    }

    public function testValues()
    {
        $this->collection = new Collection([
            '1' => 'test1',
            '2' => 'test2',
            '3' => 'test3',
            '4' => 'test4',
            '5' => 'test5',
        ]);

        $this->assertEquals([
            '1' => 'test1',
            '2' => 'test2',
            '3' => 'test3',
            '4' => 'test4',
            '5' => 'test5',
        ], $this->collection->toArray());

        $this->assertEquals([
            0 => 'test1',
            1 => 'test2',
            2 => 'test3',
            3 => 'test4',
            4 => 'test5',
        ], $this->collection->values());
    }

    public function testToArray()
    {
        $adminCollection = new Collection([
            ['name' => 'alex'],
            ['name' => 'bob'],
        ]);
        $userCollection = new Collection([
            ['name' => 'charlie'],
            ['name' => 'darwin'],
        ]);
        $this->collection = (new Collection())
            ->set('admin', $adminCollection)
            ->set('user', $userCollection);

        $this->assertEquals([
            'admin' => $adminCollection,
            'user' => $userCollection,
        ], $this->collection->toArray());
    }

    public function testToArrayNestedConversion()
    {
        $adminCollection = new Collection([
            ['name' => 'alex'],
            ['name' => 'bob'],
        ]);
        $userCollection = new Collection([
            ['name' => 'charlie'],
            ['name' => 'darwin'],
        ]);
        $this->collection = (new Collection())
            ->set('admin', $adminCollection)
            ->set('user', $userCollection);

        $this->assertEquals([
            'admin' => [
                ['name' => 'alex'],
                ['name' => 'bob'],
            ],
            'user' => [
                ['name' => 'charlie'],
                ['name' => 'darwin'],
            ],
        ], $this->collection->toArray(true));
    }
}
